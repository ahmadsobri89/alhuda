<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
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

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/billing')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_billing(): void
    {
        $this->actingAs($this->user)->get('/billing')->assertOk();
    }

    // ── Store Invoice ────────────────────────────────────────────────────────

    public function test_can_create_draft_invoice(): void
    {
        $this->actingAs($this->user)
            ->post('/billing', [
                'patient_id'   => $this->patient->id,
                'invoice_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $this->patient->id,
            'status'     => 'draft',
        ]);
    }

    public function test_invoice_number_is_auto_generated(): void
    {
        $this->actingAs($this->user)->post('/billing', [
            'patient_id'   => $this->patient->id,
            'invoice_date' => now()->format('Y-m-d'),
        ]);

        $invoice = Invoice::where('patient_id', $this->patient->id)->first();
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoice->invoice_number);
    }

    // ── Add Item ─────────────────────────────────────────────────────────────

    public function test_can_add_item_to_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'        => 'consultation',
                'description' => 'Consultation Fee',
                'quantity'    => 1,
                'unit_price'  => 50.00,
            ])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(50.00, $invoice->subtotal);
        $this->assertEquals(50.00, $invoice->total_amount);
    }

    public function test_adding_item_to_paid_invoice_requires_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'        => 'drug',
                'description' => 'Paracetamol',
                'quantity'    => 10,
                'unit_price'  => 0.50,
            ])
            ->assertSessionHasErrors('reason');

        $this->assertEquals(0, $invoice->fresh()->items()->count());
    }

    public function test_can_add_item_to_paid_invoice_with_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'        => 'drug',
                'description' => 'Paracetamol',
                'quantity'    => 10,
                'unit_price'  => 0.50,
                'reason'      => 'Doktor tambah ubat susulan selepas bayaran',
            ])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(5.00, $invoice->subtotal);
        $this->assertEquals('paid', $invoice->status);

        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.item_add']);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $invoice->id, 'subject_type' => Invoice::class]);
    }

    public function test_cannot_add_item_to_cancelled_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id, 'status' => 'cancelled']);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'        => 'drug',
                'description' => 'Paracetamol',
                'quantity'    => 10,
                'unit_price'  => 0.50,
                'reason'      => 'x',
            ])
            ->assertStatus(403);
    }

    // ── Update Item ──────────────────────────────────────────────────────────

    public function test_updating_item_on_paid_invoice_requires_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/items/{$item->id}", [
                'quantity'   => 2,
                'unit_price' => 50.00,
            ])
            ->assertSessionHasErrors('reason');

        $this->assertEquals(1, $item->fresh()->quantity);
    }

    public function test_can_update_item_on_paid_invoice_with_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/items/{$item->id}", [
                'quantity'   => 2,
                'unit_price' => 50.00,
                'reason'     => 'Pembetulan kuantiti selepas bayaran',
            ])
            ->assertSessionHas('success');

        $this->assertEquals(2, $item->fresh()->quantity);
        $this->assertEquals(100.00, $invoice->fresh()->total_amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.item_update']);
    }

    // ── Delete Item ───────────────────────────────────────────────────────────

    public function test_can_remove_item_from_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}/items/{$item->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
        $this->assertEquals(0.00, $invoice->fresh()->subtotal);
    }

    public function test_removing_item_from_paid_invoice_requires_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}/items/{$item->id}")
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('invoice_items', ['id' => $item->id]);
    }

    public function test_can_remove_item_from_paid_invoice_with_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}/items/{$item->id}", ['reason' => 'Item dimasukkan silap'])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
        $this->assertEquals(0.00, $invoice->fresh()->subtotal);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.item_remove']);
    }

    // ── Inventory Stock Sync ─────────────────────────────────────────────────

    public function test_adding_drug_item_deducts_inventory_stock(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);
        $drug    = InventoryItem::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'              => 'drug',
                'description'       => $drug->name,
                'quantity'          => 5,
                'unit_price'        => 3.00,
                'inventory_item_id' => $drug->id,
            ])
            ->assertSessionHas('success');

        $this->assertEquals(15, $drug->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $drug->id,
            'type'              => 'out',
            'quantity_delta'    => -5,
            'reference'         => $invoice->invoice_number,
        ]);
    }

    public function test_updating_drug_item_quantity_adjusts_inventory_stock(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);
        $drug    = InventoryItem::factory()->create(['stock_quantity' => 20]);
        $item    = InvoiceItem::create([
            'invoice_id' => $invoice->id, 'inventory_item_id' => $drug->id,
            'type' => 'drug', 'description' => $drug->name,
            'quantity' => 5, 'unit_price' => 3.00, 'total_price' => 15.00,
        ]);
        $drug->update(['stock_quantity' => 15]); // simulate the deduction that would have happened on add

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/items/{$item->id}", ['quantity' => 8, 'unit_price' => 3.00])
            ->assertSessionHas('success');

        // qty went 5 -> 8, i.e. 3 more units taken out of stock.
        $this->assertEquals(12, $drug->fresh()->stock_quantity);
    }

    public function test_removing_drug_item_restores_inventory_stock(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);
        $drug    = InventoryItem::factory()->create(['stock_quantity' => 15]);
        $item    = InvoiceItem::create([
            'invoice_id' => $invoice->id, 'inventory_item_id' => $drug->id,
            'type' => 'drug', 'description' => $drug->name,
            'quantity' => 5, 'unit_price' => 3.00, 'total_price' => 15.00,
        ]);

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}/items/{$item->id}")
            ->assertSessionHas('success');

        $this->assertEquals(20, $drug->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $drug->id,
            'type'              => 'adjustment',
            'quantity_delta'    => 5,
        ]);
    }

    public function test_non_drug_item_does_not_touch_inventory(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)->post("/billing/{$invoice->id}/items", [
            'type' => 'consultation', 'description' => 'Consult Fee', 'quantity' => 1, 'unit_price' => 50,
        ]);

        $this->assertEquals(0, InventoryTransaction::count());
    }

    public function test_editing_drug_item_on_paid_invoice_syncs_stock_under_rx_ledger(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $drug    = InventoryItem::factory()->create(['stock_quantity' => 20]);
        $rx      = Prescription::factory()->create([
            'patient_id' => $this->patient->id, 'invoice_id' => $invoice->id,
        ]);
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id, 'prescription_id' => $rx->id, 'inventory_item_id' => $drug->id,
            'type' => 'drug', 'description' => $drug->name,
            'quantity' => 5, 'unit_price' => 3.00, 'total_price' => 15.00,
        ]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/items/{$item->id}", [
                'quantity' => 3, 'unit_price' => 3.00, 'reason' => 'Doktor kurangkan dos',
            ])
            ->assertSessionHas('success');

        // 5 -> 3 restores 2 units, logged under the prescription's rx_number (not the invoice number)
        // so PharmacyController::reverseDispenseEffects() can still reconcile the ledger correctly.
        $this->assertEquals(22, $drug->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $drug->id,
            'reference'         => $rx->rx_number,
            'quantity_delta'    => 2,
        ]);
    }

    // ── Discount ─────────────────────────────────────────────────────────────

    public function test_can_apply_discount_to_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id]);
        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 100.00,
            'total_price' => 100.00,
        ]);
        $invoice->recalc();

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/discount", ['discount_amount' => 10.00])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(10.00, $invoice->discount_amount);
        $this->assertEquals(90.00, $invoice->total_amount);
    }

    // ── Finalize ─────────────────────────────────────────────────────────────

    public function test_can_finalize_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id, 'status' => 'draft']);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/finalize")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'unpaid']);
    }

    public function test_cannot_finalize_already_finalized_invoice(): void
    {
        $invoice = Invoice::factory()->unpaid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/finalize")
            ->assertStatus(403);
    }

    // ── Pay ──────────────────────────────────────────────────────────────────

    public function test_can_pay_invoice(): void
    {
        $invoice = Invoice::factory()->unpaid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/pay", ['payment_method' => 'cash'])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('cash', $invoice->payment_method);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals($this->user->name, $invoice->paid_by);
    }

    public function test_cannot_pay_already_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/pay", ['payment_method' => 'cash'])
            ->assertStatus(403);
    }

    public function test_payment_method_must_be_valid(): void
    {
        $invoice = Invoice::factory()->unpaid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/pay", ['payment_method' => 'bitcoin'])
            ->assertSessionHasErrors('payment_method');
    }

    // ── Update Payment Method ────────────────────────────────────────────────

    public function test_can_update_payment_method_on_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'patient_id' => $this->patient->id, 'payment_method' => 'card',
        ]);
        $originalPaidAt = $invoice->paid_at;
        $originalPaidBy = $invoice->paid_by;

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/payment-method", [
                'payment_method' => 'cash',
                'reason'         => 'Pesakit minta tukar kad ke tunai',
            ])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('cash', $invoice->payment_method);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals($originalPaidAt->timestamp, $invoice->paid_at->timestamp);
        $this->assertEquals($originalPaidBy, $invoice->paid_by);

        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.payment_method_update']);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $invoice->id, 'subject_type' => Invoice::class]);
    }

    public function test_payment_method_update_requires_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id, 'payment_method' => 'card']);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/payment-method", ['payment_method' => 'cash'])
            ->assertSessionHasErrors('reason');

        $this->assertEquals('card', $invoice->fresh()->payment_method);
    }

    public function test_payment_method_update_validates_method_whitelist(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/payment-method", ['payment_method' => 'bitcoin', 'reason' => 'x'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_cannot_update_payment_method_on_unpaid_invoice(): void
    {
        $invoice = Invoice::factory()->unpaid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/payment-method", ['payment_method' => 'cash', 'reason' => 'x'])
            ->assertStatus(403);
    }

    public function test_cannot_update_payment_method_on_cancelled_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'patient_id' => $this->patient->id, 'status' => 'cancelled', 'payment_method' => 'cash',
        ]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/payment-method", ['payment_method' => 'card', 'reason' => 'x'])
            ->assertStatus(403);
    }

    // ── Cancel ───────────────────────────────────────────────────────────────

    public function test_can_cancel_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id, 'status' => 'unpaid']);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/cancel")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
    }

    public function test_cannot_cancel_already_cancelled_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id, 'status' => 'cancelled']);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/cancel")
            ->assertStatus(403);
    }

    public function test_cancelling_paid_invoice_requires_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/cancel")
            ->assertSessionHasErrors('reason');

        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_can_cancel_paid_invoice_with_reason(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);
        $item    = InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'type'        => 'consultation',
            'description' => 'Fee',
            'quantity'    => 1,
            'unit_price'  => 50.00,
            'total_price' => 50.00,
        ]);

        $this->actingAs($this->user)
            ->patch("/billing/{$invoice->id}/cancel", ['reason' => 'Invois dibuat semula, salah pesakit'])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        // Original record & item kept for audit, not hard-deleted.
        $this->assertDatabaseHas('invoice_items', ['id' => $item->id]);
        $this->assertNotNull($invoice->paid_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.cancel']);
    }

    public function test_cancelled_paid_invoice_excluded_from_revenue_stats(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'patient_id' => $this->patient->id, 'total_amount' => 200, 'paid_at' => now(),
        ]);

        $this->actingAs($this->user)->patch("/billing/{$invoice->id}/cancel", ['reason' => 'x']);

        $this->actingAs($this->user)->get('/billing')->assertInertia(
            fn ($page) => $page->where('stats.today_revenue', 0)
        );
    }

    // ── Destroy Invoice ───────────────────────────────────────────────────────

    public function test_can_delete_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['patient_id' => $this->patient->id, 'status' => 'draft']);

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}")
            ->assertRedirect(route('billing'));

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_cannot_delete_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->delete("/billing/{$invoice->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
