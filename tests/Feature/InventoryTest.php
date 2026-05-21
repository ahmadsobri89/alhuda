<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function validItemData(array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Paracetamol Tablet',
            'generic_name'   => 'Paracetamol',
            'form'           => 'tablet',
            'classification' => 'general',
            'stock_quantity' => 100,
            'reorder_level'  => 10,
            'unit_cost'      => 0.50,
            'unit'           => 'tab',
            'status'         => 'active',
        ], $overrides);
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_inventory(): void
    {
        $this->actingAs($this->user)->get('/inventory')->assertOk();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_add_inventory_item(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['name' => 'Paracetamol Tablet']);
    }

    public function test_initial_stock_creates_transaction(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData(['stock_quantity' => 50]));

        $item = InventoryItem::where('name', 'Paracetamol Tablet')->first();

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'type'              => 'in',
            'quantity_delta'    => 50,
            'quantity_after'    => 50,
        ]);
    }

    public function test_zero_initial_stock_does_not_create_transaction(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData(['stock_quantity' => 0]));

        $item = InventoryItem::where('name', 'Paracetamol Tablet')->first();

        $this->assertDatabaseMissing('inventory_transactions', [
            'inventory_item_id' => $item->id,
        ]);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_classification_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData(['classification' => 'invalid']))
            ->assertSessionHasErrors('classification');
    }

    public function test_stock_quantity_must_not_be_negative(): void
    {
        $this->actingAs($this->user)
            ->post('/inventory', $this->validItemData(['stock_quantity' => -1]))
            ->assertSessionHasErrors('stock_quantity');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_can_update_inventory_item(): void
    {
        $item = InventoryItem::factory()->create();

        $this->actingAs($this->user)
            ->put("/inventory/{$item->id}", $this->validItemData(['name' => 'Updated Drug']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'name' => 'Updated Drug']);
    }

    // ── Stock Adjustment ─────────────────────────────────────────────────────

    public function test_can_add_stock_in(): void
    {
        $item = InventoryItem::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($this->user)
            ->patch("/inventory/{$item->id}/stock", [
                'type'      => 'in',
                'quantity'  => 30,
                'reference' => 'PO-001',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock_quantity' => 80]);
    }

    public function test_can_deduct_stock_out(): void
    {
        $item = InventoryItem::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($this->user)
            ->patch("/inventory/{$item->id}/stock", [
                'type'     => 'out',
                'quantity' => 20,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock_quantity' => 30]);
    }

    public function test_stock_out_cannot_go_below_zero(): void
    {
        $item = InventoryItem::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($this->user)
            ->patch("/inventory/{$item->id}/stock", [
                'type'     => 'out',
                'quantity' => 50,
            ])
            ->assertSessionHas('success');

        // Stock floors at 0
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock_quantity' => 0]);
    }

    public function test_can_adjust_stock_to_new_total(): void
    {
        $item = InventoryItem::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($this->user)
            ->patch("/inventory/{$item->id}/stock", [
                'type'     => 'adjustment',
                'quantity' => 75,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock_quantity' => 75]);
    }

    public function test_stock_adjustment_creates_transaction_record(): void
    {
        $item = InventoryItem::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($this->user)
            ->patch("/inventory/{$item->id}/stock", [
                'type'     => 'in',
                'quantity' => 10,
            ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'type'              => 'in',
            'quantity_delta'    => 10,
            'quantity_after'    => 60,
        ]);
    }

    // ── Discontinue ──────────────────────────────────────────────────────────

    public function test_destroy_marks_item_as_discontinued(): void
    {
        $item = InventoryItem::factory()->create();

        $this->actingAs($this->user)
            ->delete("/inventory/{$item->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'discontinued']);
    }
}
