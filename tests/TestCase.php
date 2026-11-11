<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->seed(RoleSeeder::class);
        }
    }

    /** Valid hidden spam-protection fields for public forms. */
    protected function spamFields(): array
    {
        return ['form_started_at' => encrypt(now()->subMinute()->timestamp), 'website_url' => ''];
    }

    protected function staff(string $role = 'administrator'): User
    {
        return User::factory()->staff($role)->create();
    }

    /** Freeze time on a known Monday morning so slot calculations are deterministic. */
    protected function freezeMonday(): Carbon
    {
        $now = Carbon::parse('2026-03-02 09:00:00'); // Monday
        $this->travelTo($now);

        return $now;
    }

    /** @return array{0: Branch, 1: Service} */
    protected function branchWithService(): array
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $branch->services()->attach($service);

        return [$branch->fresh('openingHours'), $service];
    }

    protected function bookingPayload(Branch $branch, Service $service, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Ama Mensah',
            'customer_email' => 'ama@example.com',
            'customer_phone' => '+233 24 123 4567',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'requested_date' => '2026-03-04', // Wednesday
            'requested_time' => '10:00',
            'customer_note' => 'First visit.',
            'privacy_consent' => '1',
        ], $this->spamFields(), $overrides);
    }
}
