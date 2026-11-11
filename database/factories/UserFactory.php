<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+233 24 '.fake()->numerify('### ####'),
            'password' => static::$password ??= 'Password-123',
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->account_type ??= User::TYPE_CUSTOMER;
            $user->is_active ??= true;
            $user->email_verified_at ??= now();
        });
    }

    public function inactive(): static
    {
        return $this->afterMaking(fn (User $u) => $u->is_active = false);
    }

    /** Staff member with the given role slug (roles are created on demand from config). */
    public function staff(string $role = 'administrator'): static
    {
        return $this->afterMaking(fn (User $u) => $u->account_type = User::TYPE_STAFF)
            ->afterCreating(function (User $u) use ($role) {
                $config = config("innes.roles.$role");
                $r = Role::firstOrCreate(['slug' => $role], ['name' => $config['name'], 'rank' => $config['rank'], 'description' => $config['description']]);
                $u->roles()->attach($r);
                $u->load('roles');
            });
    }
}
