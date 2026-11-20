<?php

namespace App\Console\Commands;

use App\Notifications\TestNotification;
use App\Services\Settings;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

/**
 * Guided set-up of customer SMS and email: asks for the provider details, writes them
 * into .env (keeping every other line), reloads the settings and sends a test.
 * Secrets are typed hidden and never printed.
 */
class SetupNotifications extends Command
{
    protected $signature = 'innes:setup-notifications';

    protected $description = 'Set up customer SMS (Arkesel/Hubtel) and email (Gmail/Hostinger) step by step and send a test';

    public function handle(): int
    {
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('No .env file found in '.base_path().'. Copy .env.example to .env first.');

            return self::FAILURE;
        }
        $values = [];

        $this->info('Step 1 of 3: SMS');
        $sms = $this->choice('Which SMS provider do you use?', ['arkesel', 'hubtel', 'skip (keep current SMS settings)'], 0);
        if ($sms === 'arkesel') {
            $this->line('  In Arkesel: SMS > Sender IDs (request e.g. InnesEye) and Settings/Developers > API keys (copy the SMS API key).');
            $values['SMS_DRIVER'] = 'arkesel';
            $values['ARKESEL_API_KEY'] = $this->askRequired('Arkesel SMS API key (typing is hidden)', secret: true);
            $values['SMS_SENDER_ID'] = $this->senderId();
        } elseif ($sms === 'hubtel') {
            $values['SMS_DRIVER'] = 'hubtel';
            $values['HUBTEL_CLIENT_ID'] = $this->askRequired('Hubtel client ID');
            $values['HUBTEL_CLIENT_SECRET'] = $this->askRequired('Hubtel client secret (typing is hidden)', secret: true);
            $values['SMS_SENDER_ID'] = $this->senderId();
        }

        $this->newLine();
        $this->info('Step 2 of 3: Email');
        $mail = $this->choice('Which mailbox sends the emails?', ['gmail', 'hostinger', 'skip (keep current email settings)'], 0);
        if ($mail !== 'skip (keep current email settings)') {
            if ($mail === 'gmail') {
                $this->line('  Gmail needs an APP PASSWORD (Google Account > Security > 2-Step Verification on > App passwords), not your normal password.');
            }
            do {
                $address = $this->askRequired('Email address that sends the messages');
                $validAddress = filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
                if (! $validAddress) {
                    $this->warn('  Type only the email address, e.g. name@gmail.com.');
                }
            } while (! $validAddress);
            $values += [
                'MAIL_MAILER' => 'smtp',
                'MAIL_SCHEME' => 'smtps',
                'MAIL_HOST' => $mail === 'gmail' ? 'smtp.gmail.com' : 'smtp.hostinger.com',
                'MAIL_PORT' => '465',
                'MAIL_USERNAME' => $address,
                'MAIL_PASSWORD' => str_replace(' ', '', $this->askRequired($mail === 'gmail' ? '16-character app password (typing is hidden)' : 'Mailbox password (typing is hidden)', secret: true)),
                'MAIL_FROM_ADDRESS' => $address,
                'MAIL_FROM_NAME' => (string) app(Settings::class)->get('business_name'),
            ];
        }

        if (! $values) {
            $this->warn('Nothing changed.');

            return self::SUCCESS;
        }

        self::writeEnv($envPath, $values);
        Artisan::call('config:clear');
        $this->newLine();
        $this->info('Saved to .env: '.implode(', ', array_keys($values)).'.');

        // Switch customer messages on in Admin > Settings.
        $settings = app(Settings::class);
        $settings->put(array_filter([
            'notify_customer_sms' => isset($values['SMS_DRIVER']) ? true : null,
            'notify_customer_emails' => isset($values['MAIL_MAILER']) ? true : null,
        ], fn ($v) => $v !== null));

        $this->newLine();
        $this->info('Step 3 of 3: Test');
        $this->reloadConfig($values);
        if (isset($values['SMS_DRIVER']) && ($phone = $this->askValid('Send a test SMS to which phone number? (leave empty to skip)', fn ($v) => SmsService::normalise($v) !== null, 'Type only a phone number, e.g. 024 123 4567.'))) {
            try {
                app(SmsService::class)->send($phone, $settings->get('business_name').': test message. SMS notifications are working.');
                $this->info('  Test SMS sent. Check your phone. (If it does not arrive, the sender ID may still be awaiting approval or the account may have no credit.)');
            } catch (\Throwable $e) {
                $this->error('  SMS failed: '.$e->getMessage());
            }
        }
        if (isset($values['MAIL_MAILER']) && ($email = $this->askValid('Send a test email to which address? (leave empty to skip)', fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) !== false, 'Type only an email address, e.g. name@gmail.com (not a password).'))) {
            try {
                Notification::route('mail', $email)->notifyNow(new TestNotification);
                $this->info('  Test email sent. Check the inbox and spam folder.');
            } catch (\Throwable $e) {
                $this->error('  Email failed: '.mb_substr($e->getMessage(), 0, 200));
            }
        }

        $this->newLine();
        $this->line('Done. Restart the site (Ctrl+C, then php artisan serve). On a live server run php artisan config:cache.');

        return self::SUCCESS;
    }

    /**
     * Sets KEY=value lines in an .env file: replaces existing (uncommented) lines, appends new
     * ones, and leaves everything else untouched.
     *
     * @param  array<string,string>  $values
     */
    public static function writeEnv(string $path, array $values): void
    {
        $lines = preg_split('/\r\n|\n/', (string) file_get_contents($path));
        foreach ($values as $key => $value) {
            $value = str_replace(["\r", "\n"], '', $value);
            $line = $key.'='.(preg_match('/[\s#"\'$]/', $value) ? '"'.addcslashes($value, '"\\$').'"' : $value);
            $found = false;
            foreach ($lines as $i => $existing) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $existing)) {
                    $lines[$i] = $line;
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $lines[] = $line;
            }
        }
        file_put_contents($path, rtrim(implode(PHP_EOL, $lines)).PHP_EOL);
    }

    /** Apply the new values to this running command, so the test uses them. */
    private function reloadConfig(array $values): void
    {
        $map = [
            'SMS_DRIVER' => 'services.sms.driver', 'SMS_SENDER_ID' => 'services.sms.sender_id', 'ARKESEL_API_KEY' => 'services.sms.arkesel_key',
            'HUBTEL_CLIENT_ID' => 'services.sms.hubtel_client_id', 'HUBTEL_CLIENT_SECRET' => 'services.sms.hubtel_client_secret',
            'MAIL_MAILER' => 'mail.default', 'MAIL_SCHEME' => 'mail.mailers.smtp.scheme', 'MAIL_HOST' => 'mail.mailers.smtp.host',
            'MAIL_PORT' => 'mail.mailers.smtp.port', 'MAIL_USERNAME' => 'mail.mailers.smtp.username', 'MAIL_PASSWORD' => 'mail.mailers.smtp.password',
            'MAIL_FROM_ADDRESS' => 'mail.from.address', 'MAIL_FROM_NAME' => 'mail.from.name',
        ];
        foreach ($values as $key => $value) {
            if (isset($map[$key])) {
                config([$map[$key] => $value]);
            }
        }
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }

    /** Asks until the answer is valid or empty (empty = skip). */
    private function askValid(string $question, \Closure $valid, string $hint): ?string
    {
        while (true) {
            $answer = trim((string) $this->ask($question));
            if ($answer === '' || $valid($answer)) {
                return $answer === '' ? null : $answer;
            }
            $this->warn('  '.$hint);
        }
    }

    private function senderId(): string
    {
        do {
            $id = (string) $this->ask('Approved sender ID (max 11 letters/numbers, e.g. InnesEye)', 'InnesEye');
            $valid = (bool) preg_match('/^[A-Za-z0-9]{1,11}$/', $id);
            if (! $valid) {
                $this->warn('  Use 1-11 letters or numbers, no spaces (e.g. InnesEye) - exactly as approved in Arkesel.');
            }
        } while (! $valid);

        return $id;
    }

    private function askRequired(string $question, bool $secret = false): string
    {
        do {
            $answer = trim((string) ($secret ? $this->secret($question) : $this->ask($question)));
        } while ($answer === '');

        return $answer;
    }
}
