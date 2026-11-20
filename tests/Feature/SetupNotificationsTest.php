<?php

namespace Tests\Feature;

use App\Console\Commands\SetupNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetupNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_env_replaces_and_appends_without_touching_other_lines(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($path, "APP_NAME=Innes\nMAIL_MAILER=log\n# SMS_DRIVER=old-comment\nDB_CONNECTION=sqlite\n");

        SetupNotifications::writeEnv($path, ['MAIL_MAILER' => 'smtp', 'SMS_DRIVER' => 'arkesel', 'MAIL_FROM_NAME' => 'Innes Eye & Visioncare']);
        $env = file_get_contents($path);

        $this->assertStringContainsString("APP_NAME=Innes\nMAIL_MAILER=smtp\n# SMS_DRIVER=old-comment\nDB_CONNECTION=sqlite\n", $env);
        $this->assertStringContainsString("SMS_DRIVER=arkesel\n", $env);
        $this->assertStringContainsString('MAIL_FROM_NAME="Innes Eye & Visioncare"', $env);
        $this->assertSame(1, substr_count($env, 'MAIL_MAILER='));
        unlink($path);
    }

    public function test_guided_setup_writes_env_and_sends_test_sms(): void
    {
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success'])]);
        $env = base_path('.env');
        $backup = is_file($env) ? file_get_contents($env) : null;
        file_put_contents($env, "APP_NAME=Innes\nMAIL_MAILER=log\n");

        try {
            $this->artisan('innes:setup-notifications')
                ->expectsChoice('Which SMS provider do you use?', 'arkesel', ['arkesel', 'hubtel', 'skip (keep current SMS settings)'])
                ->expectsQuestion('Arkesel SMS API key (typing is hidden)', 'ark-key-123')
                ->expectsQuestion('Approved sender ID (max 11 letters/numbers, e.g. InnesEye)', 'InnesEye')
                ->expectsChoice('Which mailbox sends the emails?', 'gmail', ['gmail', 'hostinger', 'skip (keep current email settings)'])
                ->expectsQuestion('Email address that sends the messages', 'inneseyecare@gmail.com')
                ->expectsQuestion('16-character app password (typing is hidden)', 'abcd efgh ijkl mnop')
                ->expectsQuestion('Send a test SMS to which phone number? (leave empty to skip)', '024 123 4567')
                ->expectsQuestion('Send a test email to which address? (leave empty to skip)', '')
                ->expectsOutputToContain('Test SMS sent')
                ->assertSuccessful();

            $written = file_get_contents($env);
            $this->assertStringContainsString('SMS_DRIVER=arkesel', $written);
            $this->assertStringContainsString('ARKESEL_API_KEY=ark-key-123', $written);
            $this->assertStringContainsString('MAIL_HOST=smtp.gmail.com', $written);
            $this->assertStringContainsString('MAIL_PASSWORD=abcdefghijklmnop', $written);
            $this->assertSame(1, substr_count($written, 'MAIL_MAILER='));
            Http::assertSent(fn (HttpRequest $r) => $r['recipients'] === ['233241234567'] && $r->hasHeader('api-key', 'ark-key-123'));
        } finally {
            $backup === null ? @unlink($env) : file_put_contents($env, $backup);
        }
    }
}
