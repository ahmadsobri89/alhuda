<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'         => Patient::factory(),
            'user_id'            => User::factory(),
            'prescribing_doctor' => fake()->name(),
            'status'             => 'pending',
            'notes'              => null,
            'drug_check_passed'  => true,
            'drug_check_notes'   => 'Tiada interaksi kritikal dikesan.',
        ];
    }

    public function dispensed(): static
    {
        return $this->state([
            'status'       => 'dispensed',
            'dispensed_at' => now(),
            'dispensed_by' => fake()->name(),
        ]);
    }
}
