<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_super_admin_from_environment(): void
    {
        config(['innes.initial_admin' => ['email' => 'owner@example.com', 'password' => 'Very-Strong-Pass-2026', 'first_name' => 'Site', 'last_name' => 'Owner']]);
        $this->artisan('innes:create-admin --from-env')->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->sole();
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_rejects_weak_or_missing_password(): void
    {
        config(['innes.initial_admin' => ['email' => 'owner@example.com', 'password' => 'short', 'first_name' => 'A', 'last_name' => 'B']]);
        $this->artisan('innes:create-admin --from-env')->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }
}
