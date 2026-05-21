<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
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

    public function test_cannot_add_item_to_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->user)
            ->post("/billing/{$invoice->id}/items", [
                'type'        => 'drug',
                'description' => 'Paracetamol',
                'quantity'    => 10,
                'unit_price'  => 0.50,
            ])
            ->assertStatus(403);
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
