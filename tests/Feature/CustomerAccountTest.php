<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private function ownedAppointment(User $owner): Appointment
    {
        [$branch, $service] = $this->branchWithService();

        return Appointment::factory()->create(['user_id' => $owner->id, 'branch_id' => $branch->id, 'service_id' => $service->id, 'requested_date' => '2026-03-11', 'requested_time' => '10:00:00']);
    }

    public function test_customer_can_register_and_is_not_staff(): void
    {
        $this->post('/register', [
            'first_name' => 'Yaw', 'last_name' => 'Boateng', 'email' => 'yaw@example.com', 'phone' => '+233 20 000 0000',
            'password' => 'Secure-Pass-123', 'password_confirmation' => 'Secure-Pass-123', 'privacy_consent' => '1',
            'account_type' => 'staff', 'is_active' => 0,
        ])->assertRedirect('/account');

        $user = User::where('email', 'yaw@example.com')->sole();
        $this->assertTrue($user->isCustomer());
        $this->assertTrue($user->is_active);
        $this->assertNotSame('Secure-Pass-123', $user->password);
        $this->assertTrue(Hash::check('Secure-Pass-123', $user->password));
        $this->get('/admin')->assertForbidden();
    }

    public function test_customer_sees_only_their_own_appointments(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->ownedAppointment($me);
        $theirs = $this->ownedAppointment($other);
        $guest = Appointment::factory()->create(['customer_email' => $me->email]);

        $this->actingAs($me)->get('/account/appointments')->assertOk()->assertSee($mine->reference_code)->assertDontSee($theirs->reference_code)->assertDontSee($guest->reference_code);
        $this->actingAs($me)->get("/account/appointments/{$mine->reference_code}")->assertOk();
        $this->actingAs($me)->get("/account/appointments/{$theirs->reference_code}")->assertNotFound();
        // Same email as a guest booking does NOT grant access – ownership is by account only.
        $this->actingAs($me)->get("/account/appointments/{$guest->reference_code}")->assertNotFound();
    }

    public function test_guest_cannot_access_customer_appointments(): void
    {
        $this->freezeMonday();
        $a = $this->ownedAppointment(User::factory()->create());

        $this->get('/account/appointments')->assertRedirect('/login');
        $this->get("/account/appointments/{$a->reference_code}")->assertRedirect('/login');
        $this->post("/account/appointments/{$a->reference_code}/cancel")->assertRedirect('/login');
    }

    public function test_customer_cannot_cancel_or_reschedule_someone_elses_appointment(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        $theirs = $this->ownedAppointment(User::factory()->create());

        $this->actingAs($me)->post("/account/appointments/{$theirs->reference_code}/cancel")->assertNotFound();
        $this->actingAs($me)->post("/account/appointments/{$theirs->reference_code}/request-reschedule", ['requested_date' => '2026-03-12', 'requested_time' => '11:00'])->assertNotFound();
        $this->assertSame(AppointmentStatus::Pending, $theirs->fresh()->status);
    }

    public function test_customer_can_cancel_own_request_before_cutoff(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        $a = $this->ownedAppointment($me);

        $this->actingAs($me)->post("/account/appointments/{$a->reference_code}/cancel")->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::Cancelled, $a->fresh()->status);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $a->id, 'new_status' => 'cancelled', 'changed_by' => $me->id]);
    }

    public function test_cancellation_cutoff_is_enforced(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        [$branch, $service] = $this->branchWithService();
        $soon = Appointment::factory()->confirmedAt(Carbon::parse('2026-03-02 15:00'))->create(['user_id' => $me->id, 'branch_id' => $branch->id, 'service_id' => $service->id, 'requested_date' => '2026-03-02', 'requested_time' => '15:00:00']);

        $this->actingAs($me)->post("/account/appointments/{$soon->reference_code}/cancel")->assertSessionHasErrors('appointment');
        $this->assertSame(AppointmentStatus::Confirmed, $soon->fresh()->status);
    }

    public function test_customer_can_request_reschedule_to_available_time(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        $a = $this->ownedAppointment($me);

        $this->actingAs($me)->post("/account/appointments/{$a->reference_code}/request-reschedule", ['requested_date' => '2026-03-12', 'requested_time' => '19:00'])->assertSessionHasErrors('requested_time');
        $this->actingAs($me)->post("/account/appointments/{$a->reference_code}/request-reschedule", ['requested_date' => '2026-03-12', 'requested_time' => '11:00'])->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertSame(AppointmentStatus::RescheduleRequested, $a->status);
        $this->assertSame('2026-03-12', $a->requested_date->toDateString());
    }

    public function test_closed_appointments_cannot_be_changed_by_customer(): void
    {
        $this->freezeMonday();
        $me = User::factory()->create();
        $a = $this->ownedAppointment($me);
        $a->status = AppointmentStatus::Completed;
        $a->save();

        $this->actingAs($me)->post("/account/appointments/{$a->reference_code}/cancel")->assertForbidden();
        $this->assertSame(AppointmentStatus::Completed, $a->fresh()->status);
    }

    public function test_customer_can_update_profile_but_not_privileged_fields(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me)->patch('/account/profile', [
            'first_name' => 'New', 'last_name' => 'Name', 'email' => 'new@example.com', 'phone' => '+233 20 111 1111',
            'account_type' => 'staff', 'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $me->refresh();
        $this->assertSame('New', $me->first_name);
        $this->assertTrue($me->isCustomer());
        $this->assertTrue($me->is_active);
    }

    public function test_customer_sees_only_own_enquiries(): void
    {
        $me = User::factory()->create();
        $mine = Enquiry::factory()->make(['subject' => 'My own question']);
        $mine->user_id = $me->id;
        $mine->save();
        Enquiry::factory()->create(['subject' => 'Someone else question']);

        $this->actingAs($me)->get('/account/enquiries')->assertSee('My own question')->assertDontSee('Someone else question');
    }

    public function test_customer_accounts_can_be_disabled_by_feature_flag(): void
    {
        config(['innes.features.customer_accounts' => false]);
        $this->get('/register')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/account')->assertNotFound();
    }
}
