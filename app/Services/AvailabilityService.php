<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calculates bookable times from branch opening hours, business booking rules
 * (lead time, booking window, slot duration, capacity) and existing confirmed appointments.
 */
class AvailabilityService
{
    public function __construct(private Settings $settings) {}

    public function slotMinutes(): int
    {
        return max(5, $this->settings->int('slot_duration_minutes'));
    }

    public function capacity(): int
    {
        return max(1, $this->settings->int('slot_capacity'));
    }

    public function earliestBookable(): Carbon
    {
        return now()->addHours(max(0, $this->settings->int('booking_lead_time_hours')));
    }

    public function latestBookableDate(): Carbon
    {
        return today()->addDays(max(1, $this->settings->int('booking_window_days')));
    }

    /** Whether the branch is open on the date and the date is inside the booking window. */
    public function isBookableDate(Branch $branch, Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();
        if ($date->gt($this->latestBookableDate()) || $date->lt($this->earliestBookable()->copy()->startOfDay())) {
            return false;
        }
        $hours = $branch->hoursFor($date->dayOfWeekIso);

        return (bool) $hours?->isOpen();
    }

    /**
     * Available start times ("H:i") for a branch on a date.
     *
     * @return list<string>
     */
    public function availableSlots(Branch $branch, Carbon $date, ?int $ignoreAppointmentId = null): array
    {
        if (! $this->isBookableDate($branch, $date)) {
            return [];
        }

        $hours = $branch->hoursFor($date->dayOfWeekIso);
        $day = $date->format('Y-m-d');
        $open = Carbon::parse($day.' '.$hours->opens_at);
        $close = Carbon::parse($day.' '.$hours->closes_at);
        $length = $this->slotMinutes();
        $earliest = $this->earliestBookable();
        $booked = $this->confirmedOn($branch, $date, $ignoreAppointmentId);

        $slots = [];
        for ($start = $open->copy(); $start->copy()->addMinutes($length)->lte($close); $start->addMinutes($length)) {
            if ($start->lt($earliest)) {
                continue;
            }
            $end = $start->copy()->addMinutes($length);
            if ($this->overlapCount($booked, $start, $end) < $this->capacity()) {
                $slots[] = $start->format('H:i');
            }
        }

        return $slots;
    }

    public function isSlotAvailable(Branch $branch, Carbon $date, string $time, ?int $ignoreAppointmentId = null): bool
    {
        return in_array(substr($time, 0, 5), $this->availableSlots($branch, $date, $ignoreAppointmentId), true);
    }

    /**
     * Whether a confirmed booking for the given window fits the opening hours and capacity.
     * Used when staff confirm, which may be inside the lead time (staff override), but never in the past.
     */
    public function fitsConfirmed(Branch $branch, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): bool
    {
        $booked = $this->confirmedOn($branch, $start, $ignoreAppointmentId);

        return $this->overlapCount($booked, $start, $end) < $this->capacity();
    }

    public function withinOpeningHours(Branch $branch, Carbon $start, Carbon $end): bool
    {
        $hours = $branch->hoursFor($start->dayOfWeekIso);
        if (! $hours?->isOpen() || ! $start->isSameDay($end)) {
            return false;
        }
        $day = $start->format('Y-m-d');

        return $start->gte(Carbon::parse($day.' '.$hours->opens_at)) && $end->lte(Carbon::parse($day.' '.$hours->closes_at));
    }

    /** @return Collection<int,Appointment> */
    private function confirmedOn(Branch $branch, Carbon $date, ?int $ignoreId): Collection
    {
        return Appointment::query()
            ->where('branch_id', $branch->id)
            ->where('status', AppointmentStatus::Confirmed->value)
            ->whereBetween('confirmed_start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get(['id', 'confirmed_start_at', 'confirmed_end_at']);
    }

    private function overlapCount(Collection $booked, Carbon $start, Carbon $end): int
    {
        return $booked->filter(fn (Appointment $a) => $a->confirmed_start_at->lt($end) && $a->confirmed_end_at->gt($start))->count();
    }
}
