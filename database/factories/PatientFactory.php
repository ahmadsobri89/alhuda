<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                    => fake()->name(),
            'ic_number'               => fake()->unique()->numerify('######-##-####'),
            'date_of_birth'           => fake()->dateTimeBetween('-80 years', '-1 day')->format('Y-m-d'),
            'gender'                  => fake()->randomElement(['male', 'female']),
            'phone'                   => '01' . fake()->numerify('#-########'),
            'email'                   => fake()->unique()->safeEmail(),
            'address'                 => fake()->streetAddress(),
            'postcode'                => fake()->numerify('#####'),
            'city'                    => fake()->city(),
            'state'                   => fake()->randomElement(['Selangor', 'Kuala Lumpur', 'Johor', 'Pulau Pinang', 'Perak']),
            'blood_type'              => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-', 'Unknown']),
            'allergies'               => null,
            'conditions'              => [],
            'emergency_contact_name'  => fake()->name(),
            'emergency_contact_phone' => '01' . fake()->numerify('#-########'),
            'visit_count'             => 0,
            'status'                  => 'active',
        ];
    }
}
