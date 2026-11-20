<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\PaystackClient;
use App\Services\Settings;
use App\Services\ShopService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Plain-language check-up of the settings that switch features on (never prints secret values). */
class Doctor extends Command
{
    protected $signature = 'innes:doctor';

    protected $description = 'Check the set-up: database, payments, shop, deposits, SMS and email';

    public function handle(PaystackClient $paystack, ShopService $shop, SmsService $sms, Settings $settings): int
    {
        $ok = fn (string $text) => $this->line("  <fg=green>[OK]</> {$text}");
        $bad = fn (string $text, string $fix) => $this->line("  <fg=red>[FIX]</> {$text}\n      <fg=yellow>-> {$fix}</>");
        $problems = 0;

        $this->info('Project');
        $this->line('  Folder:  '.base_path());
        $this->line('  .env:    '.(is_file(base_path('.env')) ? base_path('.env') : 'MISSING'));
        $branch = trim((string) @shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse --abbrev-ref HEAD 2>&1'));
        $commit = trim((string) @shell_exec('git -C '.escapeshellarg(base_path()).' log -1 --format="%h %cd" --date=short 2>&1'));
        $this->line("  Code:    branch {$branch}, last update {$commit}");
        if (str_contains(strtolower(base_path()), 'onedrive')) {
            $problems++;
            $bad('The project is inside OneDrive.', 'Use the copy outside OneDrive (e.g. C:\\Users\\<you>\\source\\repos\\Innes-Eye-and-Vision-Care).');
        }
        if (app()->configurationIsCached()) {
            $problems++;
            $bad('Settings are cached, so changes to .env are ignored.', 'Run: php artisan config:clear   (on a live server run php artisan config:cache after every .env change)');
        }

        $this->newLine();
        $this->info('Database');
        try {
            DB::connection()->getPdo();
            $ok(DB::connection()->getDriverName().': '.DB::connection()->getDatabaseName());
            $missing = collect(['orders', 'payments', 'team_members'])->reject(fn ($t) => Schema::hasTable($t))->all();
            if ($missing || ! Schema::hasColumn('appointments', 'deposit_percent')) {
                $problems++;
                $bad('The database is missing newer tables or columns.', 'Run: php artisan migrate');
            } else {
                $ok('All tables are up to date.');
            }
        } catch (\Throwable $e) {
            $problems++;
            $bad('Cannot open the database: '.mb_substr($e->getMessage(), 0, 120), 'Check the DB_ lines in .env.');
        }

        $this->newLine();
        $this->info('Payments (Paystack)');
        if ($paystack->configured()) {
            $ok('Secret key found ('.($paystack->isTestMode() ? 'TEST mode – no real money' : 'LIVE mode – real money').').');
        } else {
            $problems++;
            $raw = collect(['PAYSTACK_SECRET_KEY', 'PAYSTACK_PRIVATE_KEY'])->filter(fn ($k) => str_contains((string) @file_get_contents(base_path('.env')), $k.'='))->all();
            $bad('No Paystack secret key is loaded, so the cart and the appointment deposit are hidden.',
                $raw ? 'The line is in .env but empty or not loaded. Make it PAYSTACK_SECRET_KEY=sk_test_... (no spaces or quotes), then run php artisan config:clear and restart php artisan serve.'
                     : 'Add PAYSTACK_SECRET_KEY=sk_test_... to '.base_path('.env').' (Paystack dashboard -> Settings -> API Keys & Webhooks -> Test Secret Key), then run php artisan config:clear and restart php artisan serve.');
        }
        if (! str_starts_with((string) config('services.paystack.secret_key'), 'sk_') && $paystack->configured()) {
            $problems++;
            $bad('The secret key does not start with sk_.', 'Copy the SECRET key (sk_test_...), not the public key (pk_test_...).');
        }

        $this->newLine();
        $this->info('Shop');
        if ($shop->enabled()) {
            $buyable = Product::with('category')->get()->filter->canBeBought()->count();
            $ok("Shop is on. Products customers can buy now: {$buyable}.");
            if ($buyable === 0) {
                $this->line('      <fg=yellow>-> Give a published product a price (Admin -> Products -> Edit).</>');
            }
        } else {
            $problems++;
            $bad('Shop is hidden.', config('innes.features.shop') ? 'Fix the Paystack key above.' : 'Set FEATURE_SHOP=true in .env.');
        }

        $this->newLine();
        $this->info('Appointment deposit');
        $charges = $shop->appointmentCharges(null);
        if ($shop->appointmentPaymentsEnabled() && $charges['deposit'] > 0) {
            $ok("Customers pay {$charges['percent']}% of ".ShopService::money($charges['fee']).' = '.ShopService::money($charges['deposit']).' when booking.');
        } else {
            $problems++;
            $reason = ! config('innes.features.appointment_payments') ? 'Set FEATURE_APPOINTMENT_PAYMENTS=true in .env.'
                : (! $paystack->configured() ? 'Fix the Paystack key above.' : 'Set the consultation fee and deposit % in Admin -> Settings -> Appointment rules (deposit is '.$charges['percent'].'%).');
            $bad('No deposit is asked when booking.', $reason);
        }

        $this->newLine();
        $this->info('Customer messages');
        $driver = $sms->driver() ?: 'off';
        if ($sms->delivers()) {
            $ok("SMS provider: {$driver}.");
        } else {
            $problems++;
            $bad("SMS: {$driver} – texts are ".($driver === 'log' ? 'only written to storage/logs/laravel.log' : 'not sent').'.', 'Set SMS_DRIVER=arkesel (or hubtel/twilio) and its key in .env – see README "SMS and email notifications".');
        }
        if (! $settings->bool('notify_customer_sms')) {
            $this->line('      <fg=yellow>-> Customer SMS is switched off in Admin -> Settings.</>');
        }
        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array', ''], true)) {
            $problems++;
            $bad("Email: {$mailer} – emails are only written to the log.", 'Set MAIL_MAILER=smtp and the MAIL_ lines in .env – see README "SMS and email notifications".');
        } else {
            $ok("Email: {$mailer} via ".config('mail.mailers.smtp.host').'.');
        }

        $this->newLine();
        $problems === 0 ? $this->info('Everything is set up.') : $this->warn("{$problems} thing(s) to fix – see the -> lines above.");

        return self::SUCCESS;
    }
}
