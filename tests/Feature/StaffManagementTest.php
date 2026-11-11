<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->value('id');
    }

    public function test_super_admin_can_create_staff_with_roles(): void
    {
        $super = $this->staff('super-admin');
        $this->actingAs($super)->post('/admin/staff', [
            'first_name' => 'Abena', 'last_name' => 'Owusu', 'email' => 'abena@innes.test',
            'password' => 'Strong-Pass-123', 'password_confirmation' => 'Strong-Pass-123', 'roles' => [$this->roleId('receptionist')],
        ])->assertRedirect('/admin/staff')->assertSessionHasNoErrors();

        $member = User::where('email', 'abena@innes.test')->sole();
        $this->assertTrue($member->isStaff());
        $this->assertTrue($member->hasRole('receptionist'));
    }

    public function test_only_staff_managers_can_reach_staff_module(): void
    {
        $this->actingAs($this->staff('administrator'))->post('/admin/staff', [])->assertForbidden();
        $this->actingAs($this->staff('receptionist'))->get('/admin/staff')->assertForbidden();
    }

    public function test_staff_cannot_change_their_own_roles(): void
    {
        $super = $this->staff('super-admin');
        $this->actingAs($super)->put("/admin/staff/{$super->id}", [
            'first_name' => 'Me', 'last_name' => 'Self', 'email' => $super->email, 'roles' => [$this->roleId('receptionist')],
        ])->assertSessionHasErrors('roles');
        $this->assertTrue($super->fresh()->hasRole('super-admin'));
    }

    public function test_staff_cannot_grant_permissions_they_do_not_hold(): void
    {
        // Give administrators staff management for this test; they still must not grant super-admin.
        config(['innes.roles.administrator.permissions' => array_merge(config('innes.roles.administrator.permissions'), ['staff.manage'])]);
        $admin = $this->staff('administrator');
        $target = $this->staff('receptionist');

        $this->actingAs($admin)->put("/admin/staff/{$target->id}", [
            'first_name' => 'T', 'last_name' => 'R', 'email' => $target->email, 'roles' => [$this->roleId('super-admin')],
        ])->assertSessionHasErrors('roles.0');
        $this->assertFalse($target->fresh()->hasRole('super-admin'));

        $this->actingAs($admin)->post('/admin/staff', [
            'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@innes.test', 'password' => 'Strong-Pass-123', 'password_confirmation' => 'Strong-Pass-123',
            'roles' => [$this->roleId('super-admin')],
        ])->assertSessionHasErrors('roles.0');

        // Cannot edit a more privileged account.
        $super = $this->staff('super-admin');
        $this->actingAs($admin)->get("/admin/staff/{$super->id}/edit")->assertForbidden();
    }

    public function test_cannot_deactivate_self_or_last_super_admin(): void
    {
        $super = $this->staff('super-admin');
        $this->actingAs($super)->patch("/admin/staff/{$super->id}/status")->assertSessionHas('error');
        $this->assertTrue($super->fresh()->is_active);
    }

    public function test_deactivated_staff_cannot_sign_in(): void
    {
        $super = $this->staff('super-admin');
        $member = $this->staff('receptionist');
        $this->actingAs($super)->patch("/admin/staff/{$member->id}/status")->assertSessionHas('success');
        $this->assertFalse($member->fresh()->is_active);

        auth()->logout();
        $this->post('/login', ['email' => $member->email, 'password' => 'Password-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_settings_sections_are_permission_checked(): void
    {
        $admin = $this->staff('administrator');
        $this->actingAs($admin)->put('/admin/settings', ['section' => 'general', 'business_name' => 'Innes Eye & Visioncare Centre', 'contact_phone' => '+233 30 000 0000'])->assertSessionHasNoErrors();
        $this->assertSame('+233 30 000 0000', app(Settings::class)->get('contact_phone'));

        // Administrators cannot change booking rules (settings.system).
        $this->actingAs($admin)->put('/admin/settings', ['section' => 'system', 'booking_lead_time_hours' => 0, 'booking_window_days' => 10, 'slot_duration_minutes' => 30, 'slot_capacity' => 5, 'cancellation_cutoff_hours' => 0])->assertForbidden();

        $super = $this->staff('super-admin');
        $this->actingAs($super)->put('/admin/settings', ['section' => 'system', 'booking_lead_time_hours' => 2, 'booking_window_days' => 30, 'slot_duration_minutes' => 15, 'slot_capacity' => 2, 'cancellation_cutoff_hours' => 12, 'notify_customer_emails' => '0'])->assertSessionHasNoErrors();
        $this->assertSame(15, app(Settings::class)->int('slot_duration_minutes'));
    }
}
