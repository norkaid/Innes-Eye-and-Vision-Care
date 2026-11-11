<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentStatusChanged;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $attrs = []): Appointment
    {
        [$branch, $service] = $this->branchWithService();

        return Appointment::factory()->create(array_merge([
            'branch_id' => $branch->id, 'service_id' => $service->id,
            'requested_date' => '2026-03-04', 'requested_time' => '10:00:00',
        ], $attrs));
    }

    public function test_receptionist_can_confirm_and_history_is_recorded(): void
    {
        Notification::fake();
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $a = $this->pending();

        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", [
            'status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00', 'note' => 'Called to confirm', 'notify' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $a->status);
        $this->assertSame('2026-03-04 10:00', $a->confirmed_start_at->format('Y-m-d H:i'));
        $this->assertSame('2026-03-04 10:30', $a->confirmed_end_at->format('Y-m-d H:i'));
        $this->assertSame($staff->id, $a->handled_by);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $a->id, 'old_status' => 'pending', 'new_status' => 'confirmed', 'changed_by' => $staff->id, 'internal_note' => 'Called to confirm',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.status_changed', 'user_id' => $staff->id, 'entity_id' => $a->id]);
        Notification::assertSentOnDemand(AppointmentStatusChanged::class);
    }

    public function test_conflicting_confirmation_is_blocked_by_capacity(): void
    {
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $first = $this->pending();
        $second = Appointment::factory()->create(['branch_id' => $first->branch_id, 'service_id' => $first->service_id, 'requested_date' => '2026-03-04', 'requested_time' => '10:00:00']);

        $this->actingAs($staff)->patch("/admin/appointments/{$first->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00'])->assertSessionHasNoErrors();
        // Overlapping time at the same branch (10:15 overlaps 10:00–10:30)
        $this->actingAs($staff)->patch("/admin/appointments/{$second->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:15'])->assertSessionHasErrors('start_time');
        $this->assertSame(AppointmentStatus::Pending, $second->fresh()->status);

        // Non-overlapping time is fine.
        $this->actingAs($staff)->patch("/admin/appointments/{$second->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:30'])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::Confirmed, $second->fresh()->status);
    }

    public function test_capacity_setting_allows_parallel_appointments(): void
    {
        $this->freezeMonday();
        app(Settings::class)->put(['slot_capacity' => 2]);
        $staff = $this->staff('receptionist');
        $first = $this->pending();
        $second = Appointment::factory()->create(['branch_id' => $first->branch_id, 'service_id' => $first->service_id]);
        $third = Appointment::factory()->create(['branch_id' => $first->branch_id, 'service_id' => $first->service_id]);
        $payload = ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00'];

        $this->actingAs($staff)->patch("/admin/appointments/{$first->reference_code}", $payload)->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/admin/appointments/{$second->reference_code}", $payload)->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/admin/appointments/{$third->reference_code}", $payload)->assertSessionHasErrors('start_time');
    }

    public function test_confirmation_outside_opening_hours_or_in_past_is_rejected(): void
    {
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $a = $this->pending();

        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-08', 'start_time' => '10:00'])->assertSessionHasErrors('start_time');
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '16:45'])->assertSessionHasErrors('start_time');
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-02', 'start_time' => '08:00'])->assertSessionHasErrors('start_time');
        $this->assertSame(AppointmentStatus::Pending, $a->fresh()->status);
    }

    public function test_staff_can_propose_decline_and_complete(): void
    {
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $a = $this->pending();

        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'reschedule_proposed', 'start_date' => '2026-03-05', 'start_time' => '14:00'])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::RescheduleProposed, $a->fresh()->status);
        $this->assertSame('2026-03-05 14:00', $a->fresh()->proposed_start_at->format('Y-m-d H:i'));

        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-05', 'start_time' => '14:00'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::Completed, $a->fresh()->status);
        // proposed → confirmed → completed (factory records start without a 'submitted' entry)
        $this->assertSame(3, $a->histories()->count());

        $b = Appointment::factory()->create(['branch_id' => $a->branch_id, 'service_id' => $a->service_id]);
        $this->actingAs($staff)->patch("/admin/appointments/{$b->reference_code}", ['status' => 'declined'])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::Declined, $b->fresh()->status);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $a = $this->pending();

        // Pending cannot jump straight to completed.
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'completed'])->assertSessionHasErrors('status');
        // Closed requests cannot be reopened.
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'declined']);
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00'])->assertSessionHasErrors('status');
        $this->assertSame(AppointmentStatus::Declined, $a->fresh()->status);
    }

    public function test_unauthorised_staff_cannot_manage_appointments(): void
    {
        $this->freezeMonday();
        $a = $this->pending();
        $content = $this->staff('content-manager');

        $this->actingAs($content)->get('/admin/appointments')->assertForbidden();
        $this->actingAs($content)->get("/admin/appointments/{$a->reference_code}")->assertForbidden();
        $this->actingAs($content)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00'])->assertForbidden();
        $this->assertSame(AppointmentStatus::Pending, $a->fresh()->status);
    }

    public function test_customer_and_guest_cannot_change_status(): void
    {
        $this->freezeMonday();
        $customer = User::factory()->create();
        $a = $this->pending(['user_id' => $customer->id]);

        $this->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed'])->assertRedirect('/login');
        $this->actingAs($customer)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'confirmed', 'start_date' => '2026-03-04', 'start_time' => '10:00'])->assertForbidden();
        $this->actingAs($customer)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'completed'])->assertForbidden();
        $this->assertSame(AppointmentStatus::Pending, $a->fresh()->status);
    }

    public function test_internal_notes_are_saved_and_never_shown_to_customer(): void
    {
        $this->freezeMonday();
        $customer = User::factory()->create();
        $a = $this->pending(['user_id' => $customer->id]);
        $staff = $this->staff('receptionist');

        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}/note", ['staff_note' => 'PRIVATE-STAFF-NOTE'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/admin/appointments/{$a->reference_code}", ['status' => 'declined', 'note' => 'PRIVATE-HISTORY-NOTE']);
        $this->assertSame('PRIVATE-STAFF-NOTE', $a->fresh()->staff_note);
        $this->assertArrayNotHasKey('staff_note', $a->fresh()->toArray());

        $this->actingAs($customer)->get("/account/appointments/{$a->reference_code}")->assertOk()
            ->assertDontSee('PRIVATE-STAFF-NOTE')->assertDontSee('PRIVATE-HISTORY-NOTE');
    }

    public function test_admin_list_filters(): void
    {
        $this->freezeMonday();
        $staff = $this->staff('receptionist');
        $a = $this->pending(['customer_name' => 'Kofi Pending']);
        $b = Appointment::factory()->confirmedAt(Carbon::parse('2026-03-06 09:00'))->create(['customer_name' => 'Efua Confirmed']);

        $this->actingAs($staff)->get('/admin/appointments?status=pending')->assertSee('Kofi Pending')->assertDontSee('Efua Confirmed');
        $this->actingAs($staff)->get('/admin/appointments?q=Efua')->assertSee('Efua Confirmed')->assertDontSee('Kofi Pending');
        $this->actingAs($staff)->get('/admin/appointments?branch='.$b->branch_id)->assertSee('Efua Confirmed')->assertDontSee('Kofi Pending');
        $this->actingAs($staff)->get('/admin/appointments?service='.$a->service_id)->assertSee('Kofi Pending')->assertDontSee('Efua Confirmed');
    }
}
