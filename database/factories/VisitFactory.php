<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'      => Patient::factory(),
            'user_id'         => User::factory(),
            'doctor_name'     => fake()->name(),
            'visit_date'      => now()->format('Y-m-d'),
            'chief_complaint' => fake()->sentence(),
            'status'          => 'open',
        ];
    }

    public function closed(): static
    {
        return $this->state([
            'status'    => 'closed',
            'signed_at' => now(),
            'signed_by' => fake()->name(),
        ]);
    }
}
