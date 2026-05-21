<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_number_is_auto_generated_on_create(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->invoice_number);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoice->invoice_number);
    }

    public function test_invoice_number_increments_sequentially(): void
    {
        $first  = Invoice::factory()->create();
        $second = Invoice::factory()->create();

        $year = now()->year;
        $this->assertEquals("INV-{$year}-000001", $first->invoice_number);
        $this->assertEquals("INV-{$year}-000002", $second->invoice_number);
    }

    public function test_explicit_invoice_number_is_not_overwritten(): void
    {
        $invoice = Invoice::factory()->create(['invoice_number' => 'INV-CUSTOM-0001']);

        $this->assertEquals('INV-CUSTOM-0001', $invoice->invoice_number);
    }

    public function test_recalc_updates_subtotal_and_total(): void
    {
        $invoice = Invoice::factory()->create();

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Consultation Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'drug',
            'description' => 'Paracetamol',
            'quantity'    => 2,
            'unit_price'  => 5.00,
            'total_price' => 10.00,
        ]);

        $invoice->recalc();
        $invoice->refresh();

        $this->assertEquals(60.00, $invoice->subtotal);
        $this->assertEquals(60.00, $invoice->total_amount);
    }

    public function test_recalc_applies_discount(): void
    {
        $invoice = Invoice::factory()->create(['discount_amount' => 10.00]);

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Consultation Fee',
            'quantity'    => 1,
            'unit_price'  => 100.00,
            'total_price' => 100.00,
        ]);

        $invoice->recalc();
        $invoice->refresh();

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(90.00, $invoice->total_amount);
    }

    public function test_invoice_belongs_to_patient(): void
    {
        $patient = Patient::factory()->create();
        $invoice = Invoice::factory()->create(['patient_id' => $patient->id]);

        $this->assertEquals($patient->id, $invoice->patient->id);
    }
}
