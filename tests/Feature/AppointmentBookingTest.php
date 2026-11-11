<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentRequestReceived;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_request_is_saved_as_pending_with_history(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $response = $this->post('/appointments', $this->bookingPayload($branch, $service));

        $response->assertRedirect('/appointments/received');
        $appointment = Appointment::sole();
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertNull($appointment->confirmed_start_at);
        $this->assertNull($appointment->user_id);
        $this->assertMatchesRegularExpression('/^IEV-[A-Z2-9]{8}$/', $appointment->reference_code);
        $this->assertCount(1, $appointment->histories);
        $this->assertSame(AppointmentStatus::Pending, $appointment->histories->first()->new_status);

        $this->followRedirects($response)
            ->assertSee($appointment->reference_code)
            ->assertSee('not yet confirmed')
            ->assertDontSee('ama@example.com')
            ->assertDontSee('+233 24 123 4567');
    }

    public function test_confirmation_page_without_session_shows_no_details(): void
    {
        $this->get('/appointments/received')->assertOk()->assertDontSee('Your reference');
    }

    public function test_cannot_set_status_or_staff_fields_via_public_form(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $this->post('/appointments', $this->bookingPayload($branch, $service, [
            'status' => 'confirmed', 'confirmed_start_at' => '2026-03-04 10:00:00', 'staff_note' => 'hack', 'handled_by' => 1, 'user_id' => 99,
        ]))->assertRedirect('/appointments/received');

        $a = Appointment::sole();
        $this->assertSame(AppointmentStatus::Pending, $a->status);
        $this->assertNull($a->confirmed_start_at);
        $this->assertNull($a->staff_note);
        $this->assertNull($a->handled_by);
        $this->assertNull($a->user_id);
    }

    public function test_past_dates_are_rejected(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_date' => '2026-02-27']))
            ->assertSessionHasErrors('requested_date');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_lead_time_and_closed_days_are_enforced(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        // Today (inside the 24h lead time)
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_date' => '2026-03-02', 'requested_time' => '15:00']))
            ->assertSessionHasErrors('requested_date');
        // Sunday – closed
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_date' => '2026-03-08']))
            ->assertSessionHasErrors('requested_date');
        // Beyond the 60-day booking window
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_date' => '2026-06-03']))
            ->assertSessionHasErrors('requested_date');
        // Outside opening hours
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_time' => '18:30']))
            ->assertSessionHasErrors('requested_time');
        // Not on the slot grid
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_time' => '10:07']))
            ->assertSessionHasErrors('requested_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_invalid_branch_service_combination_is_rejected(): void
    {
        $this->freezeMonday();
        [$branch] = $this->branchWithService();
        $otherService = Service::factory()->create();

        $this->post('/appointments', $this->bookingPayload($branch, $otherService))->assertSessionHasErrors('service_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_unpublished_service_and_inactive_branch_are_rejected(): void
    {
        $this->freezeMonday();
        $branch = Branch::factory()->inactive()->create();
        $service = Service::factory()->draft()->create();
        $branch->services()->attach($service);

        $this->post('/appointments', $this->bookingPayload($branch, $service))->assertSessionHasErrors(['branch_id', 'service_id']);
    }

    public function test_required_and_invalid_fields_are_validated(): void
    {
        $this->post('/appointments', $this->spamFields())
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'branch_id', 'service_id', 'requested_date', 'requested_time', 'privacy_consent']);

        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $this->post('/appointments', $this->bookingPayload($branch, $service, [
            'customer_email' => 'not-an-email', 'customer_phone' => 'abc', 'customer_note' => str_repeat('x', 501),
        ]))->assertSessionHasErrors(['customer_email', 'customer_phone', 'customer_note']);
    }

    public function test_fully_booked_slot_is_not_offered_or_accepted(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        Appointment::factory()->confirmedAt(Carbon::parse('2026-03-04 10:00'))->create(['branch_id' => $branch->id, 'service_id' => $service->id]);

        $slots = $this->getJson("/appointments/availability?branch={$branch->id}&date=2026-03-04")->assertOk()->json('slots');
        $this->assertNotContains('10:00', $slots);
        $this->assertContains('10:30', $slots);
        $this->assertContains('08:00', $slots);
        $this->assertNotContains('17:00', $slots);

        $this->post('/appointments', $this->bookingPayload($branch, $service))->assertSessionHasErrors('requested_time');
    }

    public function test_availability_endpoint_reports_closed_days(): void
    {
        $this->freezeMonday();
        [$branch] = $this->branchWithService();

        $this->getJson("/appointments/availability?branch={$branch->id}&date=2026-03-08")
            ->assertOk()->assertJson(['slots' => []])->assertJsonPath('message', fn ($m) => str_contains($m, 'closed'));
    }

    public function test_honeypot_and_fast_submissions_are_rejected(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $this->post('/appointments', $this->bookingPayload($branch, $service, ['website_url' => 'http://spam']))->assertSessionHasErrors('form');
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['form_started_at' => encrypt(now()->timestamp)]))->assertSessionHasErrors('form');
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['form_started_at' => 'forged']))->assertSessionHasErrors('form');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_submissions_are_rate_limited(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_time' => sprintf('%02d:00', 9 + $i)]));
        }
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['requested_time' => '15:00']))->assertStatus(429);
    }

    public function test_signed_in_customer_request_is_linked_to_account(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/appointments', $this->bookingPayload($branch, $service))->assertRedirect();
        $this->assertSame($customer->id, Appointment::sole()->user_id);
    }

    public function test_acknowledgement_is_only_claimed_when_sent(): void
    {
        Notification::fake();
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        app(Settings::class)->put(['notify_customer_emails' => false]);
        $this->followingRedirects()->post('/appointments', $this->bookingPayload($branch, $service))
            ->assertSee('We have not sent an email acknowledgement');
        $this->assertDatabaseHas('notification_deliveries', ['notification_type' => 'AppointmentRequestReceived', 'status' => 'skipped']);

        app(Settings::class)->put(['notify_customer_emails' => true]);
        $this->followingRedirects()->post('/appointments', $this->bookingPayload($branch, $service, ['requested_time' => '11:00']))
            ->assertSee('An acknowledgement has been sent');
        Notification::assertSentOnDemand(AppointmentRequestReceived::class);
    }
}
