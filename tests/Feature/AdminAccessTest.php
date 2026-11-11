<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const MODULES = [
        'appointments' => '/admin/appointments',
        'enquiries' => '/admin/enquiries',
        'customers' => '/admin/customers',
        'services' => '/admin/services',
        'products' => '/admin/products',
        'categories' => '/admin/categories',
        'branches' => '/admin/branches',
        'articles' => '/admin/articles',
        'staff' => '/admin/staff',
        'settings' => '/admin/settings',
        'reports' => '/admin/reports',
        'activity' => '/admin/activity',
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        foreach (self::MODULES as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_customers_cannot_access_admin(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/admin')->assertForbidden();
        foreach (self::MODULES as $url) {
            $this->actingAs($customer)->get($url)->assertForbidden();
        }
    }

    public function test_inactive_staff_are_signed_out(): void
    {
        $staff = User::factory()->staff('super-admin')->inactive()->create();
        $this->actingAs($staff)->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public static function roleMatrix(): array
    {
        return [
            'super admin' => ['super-admin', array_keys(self::MODULES)],
            'administrator' => ['administrator', ['appointments', 'enquiries', 'customers', 'services', 'products', 'categories', 'branches', 'articles', 'settings', 'reports', 'activity']],
            'receptionist' => ['receptionist', ['appointments', 'enquiries', 'customers']],
            'content manager' => ['content-manager', ['services', 'products', 'categories', 'articles']],
        ];
    }

    #[DataProvider('roleMatrix')]
    public function test_each_role_reaches_only_permitted_modules(string $role, array $allowed): void
    {
        $user = $this->staff($role);
        $this->actingAs($user)->get('/admin')->assertOk();

        foreach (self::MODULES as $module => $url) {
            $response = $this->actingAs($user)->get($url);
            in_array($module, $allowed, true)
                ? $response->assertOk()
                : $response->assertForbidden();
        }
    }

    public function test_sidebar_only_lists_permitted_modules(): void
    {
        $this->actingAs($this->staff('content-manager'))->get('/admin')
            ->assertSee('Health articles')->assertDontSee('href="'.url('/admin/appointments').'"', false)->assertDontSee('Staff &amp; roles', false);
    }

    public function test_dashboard_figures_come_from_real_records(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        Appointment::factory()->count(3)->create(['branch_id' => $branch->id, 'service_id' => $service->id]);

        $this->actingAs($this->staff('receptionist'))->get('/admin')->assertOk()
            ->assertSeeInOrder(['Requests awaiting review', '3']);
        // Content managers don't see appointment figures at all.
        $this->actingAs($this->staff('content-manager'))->get('/admin')->assertDontSee('Requests awaiting review');
    }

    public function test_report_export_requires_permission_and_has_no_personal_data(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        Appointment::factory()->create(['branch_id' => $branch->id, 'service_id' => $service->id, 'customer_name' => 'Private Person', 'customer_email' => 'private@example.com']);

        $this->actingAs($this->staff('receptionist'))->get('/admin/reports/export')->assertForbidden();
        $csv = $this->actingAs($this->staff('administrator'))->get('/admin/reports/export')->assertOk()->streamedContent();
        $this->assertStringContainsString($branch->name, $csv);
        $this->assertStringNotContainsString('Private Person', $csv);
        $this->assertStringNotContainsString('private@example.com', $csv);
    }
}
