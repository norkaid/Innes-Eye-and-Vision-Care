<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/** Creates/updates roles from config/innes.php. Safe to run in production. */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('innes.roles') as $slug => $role) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $role['name'],
                'description' => $role['description'],
                'rank' => $role['rank'],
            ]);
        }
    }
}
