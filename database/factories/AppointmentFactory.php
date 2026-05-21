<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'       => Patient::factory(),
            'user_id'          => User::factory(),
            'doctor_name'      => fake()->name(),
            'appointment_date' => now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => fake()->randomElement(['08:00', '09:00', '10:00', '11:00', '14:00', '15:00']),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'type'             => fake()->randomElement(['new', 'follow_up', 'annual_checkup', 'procedure', 'teleconsult']),
            'reason'           => fake()->sentence(),
            'status'           => 'confirmed',
            'notes'            => null,
        ];
    }

    public function today(): static
    {
        return $this->state(['appointment_date' => now()->format('Y-m-d')]);
    }
}
