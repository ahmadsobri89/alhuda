<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => fake()->word() . ' ' . fake()->randomElement(['Tablet', 'Capsule', 'Syrup', 'Injection']),
            'generic_name'   => fake()->word(),
            'form'           => fake()->randomElement(['tablet', 'capsule', 'syrup', 'injection', 'cream']),
            'category'       => fake()->randomElement(['analgesic', 'antibiotic', 'antihypertensive', 'antidiabetic']),
            'classification' => 'general',
            'lot_number'     => strtoupper(fake()->bothify('LOT-####-??')),
            'expiry_date'    => now()->addYear()->format('Y-m-d'),
            'supplier'       => fake()->company(),
            'stock_quantity' => 100,
            'reorder_level'  => 10,
            'unit_cost'      => fake()->randomFloat(2, 0.50, 50.00),
            'unit'           => 'tab',
            'notes'          => null,
            'status'         => 'active',
        ];
    }

    public function lowStock(): static
    {
        return $this->state(['stock_quantity' => 5, 'reorder_level' => 10]);
    }

    public function expiringSoon(): static
    {
        return $this->state(['expiry_date' => now()->addDays(30)->format('Y-m-d')]);
    }
}
