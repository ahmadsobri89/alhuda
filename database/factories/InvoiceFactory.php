<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'      => Patient::factory(),
            'invoice_date'    => now()->format('Y-m-d'),
            'status'          => 'draft',
            'subtotal'        => 0.00,
            'discount_amount' => 0.00,
            'total_amount'    => 0.00,
            'notes'           => null,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(['status' => 'unpaid']);
    }

    public function paid(): static
    {
        return $this->state([
            'status'         => 'paid',
            'payment_method' => 'cash',
            'paid_at'        => now(),
            'paid_by'        => fake()->name(),
        ]);
    }
}
