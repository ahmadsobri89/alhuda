<?php

namespace Database\Factories;

use App\Models\SecurityPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityPolicy>
 */
class SecurityPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key'     => fake()->unique()->slug(3),
            'label'   => fake()->sentence(4),
            'enabled' => true,
        ];
    }
}
