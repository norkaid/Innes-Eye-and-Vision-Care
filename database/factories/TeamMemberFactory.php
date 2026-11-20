<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'job_title' => 'Optometrist',
            'qualifications' => null,
            'bio' => $this->faker->sentence(12),
            'is_published' => true,
            'display_order' => 0,
        ];
    }
}
