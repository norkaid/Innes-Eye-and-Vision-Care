<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\AppointmentRequestReceived;
use App\Notifications\AppointmentStatusChanged;
use App\Notifications\StaffNewAppointmentRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /** Unambiguous characters (no 0/O, 1/I/L) for reference codes. */
    private const REF_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(
        private AvailabilityService $availability,
        private NotificationService $notifications,
        private Settings $settings,
        private AuditLogger $audit,
    ) {}

    /**
     * Create a pending appointment request from validated public input.
     *
     * @param  array<string,mixed>  $data
     * @return array{appointment: Appointment, acknowledged: bool}
     */
    public function createRequest(array $data, ?User $user = null): array
    {
        $appointment = DB::transaction(function () use ($data, $user) {
            $appointment = new Appointment($data);
            $appointment->reference_code = $this->generateReference();
            $appointment->status = AppointmentStatus::Pending;
            $appointment->status_updated_at = now();
            if ($user && $user->isCustomer()) {
                $appointment->user_id = $user->id;
            }
            $appointment->save();

            $this->recordHistory($appointment, null, AppointmentStatus::Pending, null, 'Request submitted online');

            return $appointment;
        });

        $acknowledged = $this->notifications->toCustomer($appointment->customer_email, new AppointmentRequestReceived($appointment), $appointment);
        $this->notifications->toStaff(new StaffNewAppointmentRequest($appointment), $appointment);

        return ['appointment' => $appointment, 'acknowledged' => $acknowledged];
    }

    /**
     * Staff status change. Validates the transition, enforces capacity for confirmations
     * inside a transaction with a branch row lock, and records history and audit entries.
     *
     * @param  array{status:string, start_date?:?string, start_time?:?string, note?:?string, notify?:bool}  $input
     */
    public function changeStatus(Appointment $appointment, array $input, User $staff): Appointment
    {
        $target = AppointmentStatus::from($input['status']);

        $updated = DB::transaction(function () use ($appointment, $target, $input, $staff) {
            // Lock the branch row so concurrent confirmations for the same branch are serialised.
            Branch::query()->whereKey($appointment->branch_id)->lockForUpdate()->first();
            /** @var Appointment $fresh */
            $fresh = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $current = $fresh->status;

            $isSameStatusProposal = $current === $target && $target === AppointmentStatus::RescheduleProposed;
            if (! $current->canTransitionTo($target) && ! $isSameStatusProposal) {
                throw ValidationException::withMessages([
                    'status' => "An appointment that is \"{$current->label()}\" cannot be changed to \"{$target->label()}\".",
                ]);
            }

            if (in_array($target, [AppointmentStatus::Confirmed, AppointmentStatus::RescheduleProposed], true)) {
                $start = $this->resolveStart($fresh, $input);
                $end = $start->copy()->addMinutes($this->availability->slotMinutes());
                $branch = Branch::with('openingHours')->findOrFail($fresh->branch_id);

                if ($start->lt(now())) {
                    throw ValidationException::withMessages(['start_time' => 'The appointment time must be in the future.']);
                }
                if (! $this->availability->withinOpeningHours($branch, $start, $end)) {
                    throw ValidationException::withMessages(['start_time' => 'The selected time is outside the branch opening hours.']);
                }
                if ($target === AppointmentStatus::Confirmed && ! $this->availability->fitsConfirmed($branch, $start, $end, $fresh->id)) {
                    throw ValidationException::withMessages(['start_time' => 'This time conflicts with another confirmed appointment at this branch (capacity reached). Choose another time.']);
                }

                if ($target === AppointmentStatus::Confirmed) {
                    $fresh->confirmed_start_at = $start;
                    $fresh->confirmed_end_at = $end;
                    $fresh->proposed_start_at = null;
                } else {
                    $fresh->proposed_start_at = $start;
                    $fresh->confirmed_start_at = null;
                    $fresh->confirmed_end_at = null;
                }
            }

            if (in_array($target, [AppointmentStatus::Declined, AppointmentStatus::Cancelled], true)) {
                $fresh->confirmed_start_at = null;
                $fresh->confirmed_end_at = null;
                $fresh->proposed_start_at = null;
            }

            $fresh->status = $target;
            $fresh->handled_by = $staff->id;
            $fresh->status_updated_at = now();
            $fresh->save();

            $this->recordHistory($fresh, $current, $target, $staff, $input['note'] ?? null);
            $this->audit->log('appointment.status_changed', $fresh, ['from' => $current->value, 'to' => $target->value]);

            return $fresh;
        });

        if (($input['notify'] ?? true) && in_array($target, [AppointmentStatus::Confirmed, AppointmentStatus::RescheduleProposed, AppointmentStatus::Declined, AppointmentStatus::Cancelled], true)) {
            $this->notifications->toCustomer($updated->customer_email, new AppointmentStatusChanged($updated), $updated);
        }

        return $updated;
    }

    public function updateStaffNote(Appointment $appointment, ?string $note, User $staff): void
    {
        $appointment->staff_note = $note;
        $appointment->save();
        $this->audit->log('appointment.note_updated', $appointment);
    }

    /** Whether the customer is still before the business cut-off for changes. */
    public function beforeCutoff(Appointment $appointment): bool
    {
        $cutoff = $this->settings->int('cancellation_cutoff_hours');

        return $appointment->effectiveStart()->gt(now()->addHours($cutoff));
    }

    public function customerCancel(Appointment $appointment, User $customer): void
    {
        $this->assertCustomerChange($appointment, $appointment->status->customerCanCancel());

        DB::transaction(function () use ($appointment, $customer) {
            $old = $appointment->status;
            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->confirmed_start_at = null;
            $appointment->confirmed_end_at = null;
            $appointment->proposed_start_at = null;
            $appointment->status_updated_at = now();
            $appointment->save();
            $this->recordHistory($appointment, $old, AppointmentStatus::Cancelled, $customer, 'Cancelled by customer');
        });
    }

    /** @param array{requested_date:string, requested_time:?string, reason:?string} $data */
    public function customerRequestReschedule(Appointment $appointment, array $data, User $customer): void
    {
        $this->assertCustomerChange($appointment, $appointment->status->customerCanRequestReschedule());

        DB::transaction(function () use ($appointment, $data, $customer) {
            $old = $appointment->status;
            $appointment->requested_date = $data['requested_date'];
            $appointment->requested_time = $data['requested_time'] ?? null;
            $appointment->status = AppointmentStatus::RescheduleRequested;
            // A confirmed slot is released while the clinic reviews the new preference.
            $appointment->confirmed_start_at = null;
            $appointment->confirmed_end_at = null;
            $appointment->proposed_start_at = null;
            $appointment->status_updated_at = now();
            $appointment->save();
            $this->recordHistory($appointment, $old, AppointmentStatus::RescheduleRequested, $customer, 'Customer requested a new time');
        });

        $this->notifications->toStaff(new StaffNewAppointmentRequest($appointment, true), $appointment);
    }

    private function assertCustomerChange(Appointment $appointment, bool $statusAllows): void
    {
        if (! $statusAllows) {
            throw ValidationException::withMessages(['appointment' => 'This appointment can no longer be changed online. Please contact the branch.']);
        }
        if (! $this->beforeCutoff($appointment)) {
            $hours = $this->settings->int('cancellation_cutoff_hours');
            throw ValidationException::withMessages(['appointment' => "Changes must be made at least {$hours} hours before the appointment. Please contact the branch directly."]);
        }
    }

    private function resolveStart(Appointment $appointment, array $input): Carbon
    {
        $date = $input['start_date'] ?? $appointment->requested_date->format('Y-m-d');
        $time = $input['start_time'] ?? $appointment->requested_time;
        if (! $time) {
            throw ValidationException::withMessages(['start_time' => 'Please choose a time.']);
        }

        return Carbon::parse($date.' '.substr($time, 0, 5));
    }

    private function recordHistory(Appointment $appointment, ?AppointmentStatus $old, AppointmentStatus $new, ?User $by, ?string $note): void
    {
        $history = new AppointmentStatusHistory([
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'changed_by' => $by?->id,
            'internal_note' => $note,
        ]);
        $appointment->histories()->save($history);
    }

    public function generateReference(): string
    {
        do {
            $code = 'IEV-';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::REF_ALPHABET[random_int(0, strlen(self::REF_ALPHABET) - 1)];
            }
        } while (Appointment::where('reference_code', $code)->exists());

        return $code;
    }
}
