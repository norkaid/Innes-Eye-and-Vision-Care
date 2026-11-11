<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_follow_opening_hours_and_slot_length(): void
    {
        $this->freezeMonday();
        [$branch] = $this->branchWithService();
        $slots = app(AvailabilityService::class)->availableSlots($branch, Carbon::parse('2026-03-04'));

        $this->assertSame('08:00', $slots[0]);
        $this->assertSame('16:30', end($slots));
        $this->assertCount(18, $slots);
    }

    public function test_settings_change_slot_length_and_lead_time(): void
    {
        $this->freezeMonday();
        [$branch] = $this->branchWithService();
        app(Settings::class)->put(['slot_duration_minutes' => 60, 'booking_lead_time_hours' => 2]);
        $service = app(AvailabilityService::class);

        $this->assertCount(9, $service->availableSlots($branch, Carbon::parse('2026-03-04')));
        // Today: 09:00 now + 2h lead time → first slot 11:00
        $this->assertSame('11:00', $service->availableSlots($branch, Carbon::parse('2026-03-02'))[0]);
    }

    public function test_confirmed_appointments_reduce_availability(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        Appointment::factory()->confirmedAt(Carbon::parse('2026-03-04 08:00'), 60)->create(['branch_id' => $branch->id, 'service_id' => $service->id]);
        // Pending requests do NOT block slots.
        Appointment::factory()->create(['branch_id' => $branch->id, 'service_id' => $service->id, 'requested_date' => '2026-03-04', 'requested_time' => '12:00:00']);

        $slots = app(AvailabilityService::class)->availableSlots($branch, Carbon::parse('2026-03-04'));
        $this->assertNotContains('08:00', $slots);
        $this->assertNotContains('08:30', $slots);
        $this->assertContains('09:00', $slots);
        $this->assertContains('12:00', $slots);
    }
}
