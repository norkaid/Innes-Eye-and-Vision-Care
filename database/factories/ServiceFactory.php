<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Service '.fake()->unique()->words(3, true),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'is_published' => true,
            'display_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
