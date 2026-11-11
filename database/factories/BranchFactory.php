<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test Branch '.fake()->unique()->numberBetween(1, 99999),
            'address' => 'Test address',
            'city' => 'Accra',
            'region' => 'Greater Accra',
            'phone' => '+233 30 000 0000',
            'is_active' => true,
        ];
    }

    /** Open Monday–Saturday 08:00–17:00, closed Sunday. */
    public function configure(): static
    {
        return $this->afterCreating(function (Branch $branch) {
            foreach (Branch::DAYS as $day => $_) {
                $branch->openingHours()->create([
                    'day_of_week' => $day,
                    'is_closed' => $day === 7,
                    'opens_at' => $day === 7 ? null : '08:00',
                    'closes_at' => $day === 7 ? null : '17:00',
                ]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
