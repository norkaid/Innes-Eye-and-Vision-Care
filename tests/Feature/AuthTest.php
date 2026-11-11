<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_redirects_to_admin_and_customer_to_account(): void
    {
        $staff = $this->staff('receptionist');
        $this->post('/login', ['email' => $staff->email, 'password' => 'Password-123'])->assertRedirect('/admin');
        $this->assertNotNull($staff->fresh()->last_login_at);
        $this->post('/logout');

        $customer = User::factory()->create();
        $this->post('/login', ['email' => $customer->email, 'password' => 'Password-123'])->assertRedirect('/account');
    }

    public function test_wrong_password_and_inactive_accounts_fail(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $inactive = User::factory()->inactive()->create();
        $this->post('/login', ['email' => $inactive->email, 'password' => 'Password-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_per_account(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'Password-123'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        // Same response for unknown emails (no account enumeration).
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->get('/reset-password/'.$n->token.'?email='.$user->email)->assertOk();
            $this->post('/reset-password', ['token' => $n->token, 'email' => $user->email, 'password' => 'Brand-New-Pass-1', 'password_confirmation' => 'Brand-New-Pass-1'])
                ->assertRedirect('/login');

            return true;
        });
        $this->post('/login', ['email' => $user->email, 'password' => 'Brand-New-Pass-1'])->assertRedirect('/account');
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->post('/register', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'privacy_consent' => '1'])
            ->assertSessionHasErrors('password');
    }
}
