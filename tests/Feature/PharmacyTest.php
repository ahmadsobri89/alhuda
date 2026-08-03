<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->patient = Patient::factory()->create();
    }

    private function validPrescriptionData(array $overrides = []): array
    {
        return array_merge([
            'patient_id'         => $this->patient->id,
            'prescribing_doctor' => 'Dr. Ahmad',
            'notes'              => null,
            'items'              => [
                [
                    'drug_name'    => 'Paracetamol 500mg',
                    'dosage'       => '500mg',
                    'frequency'    => 'TDS',
                    'duration'     => '3 hari',
                    'quantity'     => 9,
                    'instructions' => 'Lepas makan',
                ],
            ],
        ], $overrides);
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/pharmacy')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_pharmacy(): void
    {
        $this->actingAs($this->user)->get('/pharmacy')->assertOk();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_create_prescription(): void
    {
        $this->actingAs($this->user)
            ->post('/pharmacy/prescriptions', $this->validPrescriptionData())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', [
            'patient_id'         => $this->patient->id,
            'prescribing_doctor' => 'Dr. Ahmad',
            'status'             => 'pending',
        ]);

        $rx = Prescription::where('patient_id', $this->patient->id)->first();
        $this->assertCount(1, $rx->items);
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        $this->actingAs($this->user)
            ->post('/pharmacy/prescriptions', $this->validPrescriptionData(['items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_prescription_item_drug_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post('/pharmacy/prescriptions', $this->validPrescriptionData([
                'items' => [['drug_name' => '', 'quantity' => 5]],
            ]))
            ->assertSessionHasErrors('items.0.drug_name');
    }

    public function test_rx_number_is_auto_generated(): void
    {
        $this->actingAs($this->user)
            ->post('/pharmacy/prescriptions', $this->validPrescriptionData());

        $rx = Prescription::where('patient_id', $this->patient->id)->first();
        $this->assertMatchesRegularExpression('/^Rx-\d{4}-\d{4}$/', $rx->rx_number);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_can_update_pending_prescription(): void
    {
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);
        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Old Drug',
            'quantity'        => 5,
        ]);

        $this->actingAs($this->user)
            ->put("/pharmacy/prescriptions/{$rx->id}", $this->validPrescriptionData([
                'items' => [
                    ['drug_name' => 'New Drug', 'quantity' => 10],
                ],
            ]))
            ->assertSessionHas('success');

        $rx->refresh();
        $this->assertCount(1, $rx->fresh()->items);
        $this->assertEquals('New Drug', $rx->fresh()->items->first()->drug_name);
    }

    private function dispenseWithInvoice(array $invoiceOverrides = []): array
    {
        $rx = Prescription::factory()->dispensed()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Drug A', 'quantity' => 5]);

        $invoice = Invoice::factory()->create(array_merge([
            'patient_id'   => $this->patient->id,
            'subtotal'     => 10,
            'total_amount' => 10,
        ], $invoiceOverrides));

        InvoiceItem::create([
            'invoice_id'       => $invoice->id,
            'prescription_id'  => $rx->id,
            'type'             => 'drug',
            'description'      => 'Drug A',
            'quantity'         => 5,
            'unit_price'       => 2,
            'total_price'      => 10,
        ]);

        $rx->update(['invoice_id' => $invoice->id]);

        return [$rx, $invoice];
    }

    public function test_can_update_dispensed_prescription_resyncs_unpaid_invoice_in_place(): void
    {
        [$rx, $invoice] = $this->dispenseWithInvoice(['status' => 'unpaid']);

        $this->actingAs($this->user)
            ->put("/pharmacy/prescriptions/{$rx->id}", $this->validPrescriptionData([
                'items' => [['drug_name' => 'Drug B', 'quantity' => 3]],
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('invoices', 1);
        $item = InvoiceItem::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('Drug B', $item->description);
    }

    public function test_can_update_dispensed_prescription_with_paid_invoice_resyncs_same_invoice(): void
    {
        [$rx, $invoice] = $this->dispenseWithInvoice([
            'status' => 'paid', 'payment_method' => 'cash', 'paid_at' => now(), 'paid_by' => 'Front Desk',
        ]);

        $this->actingAs($this->user)
            ->put("/pharmacy/prescriptions/{$rx->id}", $this->validPrescriptionData([
                'items' => [['drug_name' => 'Drug B', 'quantity' => 3]],
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('invoices', 1);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $item = InvoiceItem::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('Drug B', $item->description);
    }

    public function test_can_update_dispensed_prescription_with_cancelled_invoice_resyncs_same_invoice(): void
    {
        [$rx, $invoice] = $this->dispenseWithInvoice(['status' => 'cancelled']);

        $this->actingAs($this->user)
            ->put("/pharmacy/prescriptions/{$rx->id}", $this->validPrescriptionData([
                'items' => [['drug_name' => 'Drug B', 'quantity' => 3]],
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('invoices', 1);
        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_update_dispensed_prescription_with_no_resolvable_invoice_link_errors_without_creating_phantom_invoice(): void
    {
        $rx = Prescription::factory()->dispensed()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Drug A', 'quantity' => 5]);

        // Legacy invoice whose item was never linked back to this prescription
        // (invoice_items.prescription_id NULL, prescriptions.invoice_id NULL).
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'drug',
            'description' => 'Drug A',
            'quantity'    => 5,
            'unit_price'  => 2,
            'total_price' => 10,
        ]);

        $this->actingAs($this->user)
            ->put("/pharmacy/prescriptions/{$rx->id}", $this->validPrescriptionData())
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('invoices', 1);
    }

    // ── Status Update ────────────────────────────────────────────────────────

    public function test_can_update_prescription_status_to_verifying(): void
    {
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch("/pharmacy/prescriptions/{$rx->id}/status", ['status' => 'verifying'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', ['id' => $rx->id, 'status' => 'verifying']);
    }

    public function test_can_dispense_prescription(): void
    {
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch("/pharmacy/prescriptions/{$rx->id}/status", ['status' => 'dispensed'])
            ->assertSessionHas('success');

        $rx->refresh();
        $this->assertEquals('dispensed', $rx->status);
        $this->assertNotNull($rx->dispensed_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch("/pharmacy/prescriptions/{$rx->id}/status", ['status' => 'approved'])
            ->assertSessionHasErrors('status');
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_can_delete_prescription(): void
    {
        $rx = Prescription::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete("/pharmacy/prescriptions/{$rx->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('prescriptions', ['id' => $rx->id]);
    }

    public function test_can_delete_dispensed_prescription_with_unpaid_invoice_cleans_up_invoice(): void
    {
        [$rx, $invoice] = $this->dispenseWithInvoice(['status' => 'unpaid']);

        $this->actingAs($this->user)
            ->delete("/pharmacy/prescriptions/{$rx->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('prescriptions', ['id' => $rx->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
        $this->assertEquals(0, $invoice->fresh()->total_amount);
    }

    public function test_can_delete_dispensed_prescription_with_paid_invoice_cleans_up_invoice(): void
    {
        [$rx, $invoice] = $this->dispenseWithInvoice([
            'status' => 'paid', 'payment_method' => 'cash', 'paid_at' => now(), 'paid_by' => 'Front Desk',
        ]);

        $this->actingAs($this->user)
            ->delete("/pharmacy/prescriptions/{$rx->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('prescriptions', ['id' => $rx->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->total_amount);
    }

    public function test_delete_dispensed_prescription_with_no_resolvable_invoice_link_errors(): void
    {
        $rx = Prescription::factory()->dispensed()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Drug A', 'quantity' => 5]);

        $this->actingAs($this->user)
            ->delete("/pharmacy/prescriptions/{$rx->id}")
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('prescriptions', ['id' => $rx->id]);
    }
}
