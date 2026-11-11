<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'service_id' => Service::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '+233 24 000 0000',
            'requested_date' => now()->next('Wednesday')->addWeek()->toDateString(),
            'requested_time' => '10:00:00',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Appointment $a) {
            $a->reference_code ??= 'IEV-'.Str::upper(Str::random(8));
            $a->status ??= AppointmentStatus::Pending;
        });
    }

    public function confirmedAt(\DateTimeInterface $start, int $minutes = 30): static
    {
        return $this->afterMaking(function (Appointment $a) use ($start, $minutes) {
            $a->status = AppointmentStatus::Confirmed;
            $a->confirmed_start_at = $start;
            $a->confirmed_end_at = Carbon::instance($start)->addMinutes($minutes);
        });
    }
}
