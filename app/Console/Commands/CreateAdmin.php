<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates (or promotes) the first Super Admin securely. No default password is ever committed.
 *
 *   php artisan innes:create-admin                 (interactive prompts)
 *   php artisan innes:create-admin --from-env      (reads INITIAL_ADMIN_* from .env)
 */
class CreateAdmin extends Command
{
    protected $signature = 'innes:create-admin {--from-env : Read INITIAL_ADMIN_EMAIL / INITIAL_ADMIN_PASSWORD from the environment}';

    protected $description = 'Create a Super Admin staff account';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder', '--force' => true]);

        if ($this->option('from-env')) {
            $cfg = config('innes.initial_admin');
            $data = ['first_name' => $cfg['first_name'], 'last_name' => $cfg['last_name'], 'email' => $cfg['email'], 'password' => $cfg['password'], 'password_confirmation' => $cfg['password']];
        } else {
            $data = [
                'first_name' => $this->ask('First name'),
                'last_name' => $this->ask('Last name'),
                'email' => $this->ask('Email address'),
                'password' => $pw = $this->secret('Password (min 10 chars, mixed case and a number)'),
                'password_confirmation' => $this->secret('Confirm password'),
            ];
        }

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::withTrashed()->firstOrNew(['email' => strtolower($data['email'])]);
        if ($user->exists && $user->isCustomer()) {
            $this->error('That email belongs to a customer account. Use a different email for staff.');

            return self::FAILURE;
        }
        $user->fill(['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'password' => $data['password']]);
        $user->account_type = User::TYPE_STAFF;
        $user->is_active = true;
        $user->deleted_at = null;
        $user->save();
        $user->roles()->syncWithoutDetaching([Role::where('slug', 'super-admin')->value('id')]);

        $this->info("Super Admin ready: {$user->email}");
        if ($this->option('from-env')) {
            $this->warn('Remove INITIAL_ADMIN_PASSWORD from your .env file now.');
        }

        return self::SUCCESS;
    }
}
