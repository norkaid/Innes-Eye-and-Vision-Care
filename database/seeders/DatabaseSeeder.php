<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Always safe: roles and default settings.
     * Demo content and demo staff accounts are only seeded outside production.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoContentSeeder::class);
        }
    }
}
