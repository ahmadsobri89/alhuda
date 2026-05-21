<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_low_stock_when_quantity_at_reorder_level(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 10,
            'reorder_level'  => 10,
        ]);

        $this->assertTrue($item->is_low_stock);
    }

    public function test_is_not_low_stock_when_quantity_above_reorder_level(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 11,
            'reorder_level'  => 10,
        ]);

        $this->assertFalse($item->is_low_stock);
    }

    public function test_is_expiring_when_within_90_days(): void
    {
        $item = InventoryItem::factory()->create([
            'expiry_date' => now()->addDays(60)->format('Y-m-d'),
        ]);

        $this->assertTrue($item->is_expiring);
    }

    public function test_is_not_expiring_when_beyond_90_days(): void
    {
        $item = InventoryItem::factory()->create([
            'expiry_date' => now()->addDays(120)->format('Y-m-d'),
        ]);

        $this->assertFalse($item->is_expiring);
    }

    public function test_is_expired_when_past_expiry_date(): void
    {
        $item = InventoryItem::factory()->create([
            'expiry_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->assertTrue($item->is_expired);
    }

    public function test_is_not_expired_when_expiry_is_today_or_future(): void
    {
        $item = InventoryItem::factory()->create([
            'expiry_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $this->assertFalse($item->is_expired);
    }

    public function test_flags_includes_low_for_low_stock(): void
    {
        $item = InventoryItem::factory()->lowStock()->create();

        $this->assertContains('low', $item->flags);
    }

    public function test_flags_includes_expired(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 100,
            'reorder_level'  => 10,
            'expiry_date'    => now()->subDay()->format('Y-m-d'),
        ]);

        $this->assertContains('expired', $item->flags);
        $this->assertNotContains('expiring', $item->flags);
    }

    public function test_flags_includes_expiring_when_not_expired(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 100,
            'reorder_level'  => 10,
            'expiry_date'    => now()->addDays(30)->format('Y-m-d'),
        ]);

        $this->assertContains('expiring', $item->flags);
    }

    public function test_flags_includes_classification(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 100,
            'reorder_level'  => 10,
            'classification' => 'poison_b',
            'expiry_date'    => now()->addDays(200)->format('Y-m-d'),
        ]);

        $this->assertContains('poison_b', $item->flags);
    }

    public function test_stock_value_is_quantity_times_unit_cost(): void
    {
        $item = InventoryItem::factory()->create([
            'stock_quantity' => 50,
            'unit_cost'      => 4.50,
        ]);

        $this->assertEquals(225.00, $item->stock_value);
    }
}
