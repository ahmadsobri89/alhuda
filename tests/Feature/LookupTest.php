<?php

namespace Tests\Feature;

use App\Models\LookupCategory;
use App\Models\LookupValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LookupCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = LookupCategory::create([
            'group'    => 'patient',
            'slug'     => 'test_category',
            'name_ms'  => 'Kategori Ujian',
            'name_en'  => 'Test Category',
            'sort_order' => 1,
        ]);
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_guests_cannot_add_lookup_value(): void
    {
        $this->post("/settings/lookup/{$this->category->id}/values", [
            'code'     => 'test',
            'label_ms' => 'Ujian',
            'label_en' => 'Test',
        ])->assertRedirect('/login');
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_add_value_to_category(): void
    {
        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'new_val',
                'label_ms' => 'Nilai Baru',
                'label_en' => 'New Value',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lookup_values', [
            'category_id' => $this->category->id,
            'code'        => 'new_val',
            'label_ms'    => 'Nilai Baru',
        ]);
    }

    public function test_code_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'label_ms' => 'Nilai Baru',
                'label_en' => 'New Value',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_label_ms_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'val',
                'label_en' => 'Value',
            ])
            ->assertSessionHasErrors('label_ms');
    }

    public function test_label_en_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'val',
                'label_ms' => 'Nilai',
            ])
            ->assertSessionHasErrors('label_en');
    }

    public function test_cannot_add_duplicate_code_in_same_category(): void
    {
        LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'existing',
            'label_ms'    => 'Sedia Ada',
            'label_en'    => 'Existing',
        ]);

        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'existing',
                'label_ms' => 'Lain',
                'label_en' => 'Another',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_same_code_can_exist_in_different_categories(): void
    {
        $other = LookupCategory::create([
            'group'      => 'patient',
            'slug'       => 'other_cat',
            'name_ms'    => 'Lain',
            'name_en'    => 'Other',
            'sort_order' => 2,
        ]);

        LookupValue::create([
            'category_id' => $other->id,
            'code'        => 'shared_code',
            'label_ms'    => 'Kongsi',
            'label_en'    => 'Shared',
        ]);

        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'shared_code',
                'label_ms' => 'Kongsi',
                'label_en' => 'Shared',
            ])
            ->assertSessionHas('success');
    }

    public function test_sort_order_defaults_to_next_available(): void
    {
        LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'first',
            'label_ms'    => 'Pertama',
            'label_en'    => 'First',
            'sort_order'  => 5,
        ]);

        $this->actingAs($this->user)
            ->post("/settings/lookup/{$this->category->id}/values", [
                'code'     => 'second',
                'label_ms' => 'Kedua',
                'label_en' => 'Second',
            ]);

        $second = LookupValue::where('code', 'second')->first();
        $this->assertEquals(6, $second->sort_order);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_can_update_lookup_value(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'old_code',
            'label_ms'    => 'Label Lama',
            'label_en'    => 'Old Label',
        ]);

        $this->actingAs($this->user)
            ->put("/settings/lookup/{$this->category->id}/values/{$value->id}", [
                'code'     => 'new_code',
                'label_ms' => 'Label Baru',
                'label_en' => 'New Label',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lookup_values', [
            'id'       => $value->id,
            'code'     => 'new_code',
            'label_ms' => 'Label Baru',
        ]);
    }

    public function test_cannot_update_to_duplicate_code_in_same_category(): void
    {
        LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'taken',
            'label_ms'    => 'Diambil',
            'label_en'    => 'Taken',
        ]);

        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'mine',
            'label_ms'    => 'Milik',
            'label_en'    => 'Mine',
        ]);

        $this->actingAs($this->user)
            ->put("/settings/lookup/{$this->category->id}/values/{$value->id}", [
                'code'     => 'taken',
                'label_ms' => 'Milik',
                'label_en' => 'Mine',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_can_update_value_keeping_same_code(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'same_code',
            'label_ms'    => 'Lama',
            'label_en'    => 'Old',
        ]);

        $this->actingAs($this->user)
            ->put("/settings/lookup/{$this->category->id}/values/{$value->id}", [
                'code'     => 'same_code',
                'label_ms' => 'Baru',
                'label_en' => 'New',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lookup_values', ['id' => $value->id, 'label_ms' => 'Baru']);
    }

    // ── Toggle ────────────────────────────────────────────────────────────────

    public function test_can_toggle_value_inactive(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'toggleme',
            'label_ms'    => 'Togol',
            'label_en'    => 'Toggle',
            'is_active'   => true,
        ]);

        $this->actingAs($this->user)
            ->patch("/settings/lookup/{$this->category->id}/values/{$value->id}/toggle")
            ->assertSessionHas('success');

        $this->assertFalse($value->fresh()->is_active);
    }

    public function test_can_toggle_value_back_to_active(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'inactive_val',
            'label_ms'    => 'Tidak Aktif',
            'label_en'    => 'Inactive',
            'is_active'   => false,
        ]);

        $this->actingAs($this->user)
            ->patch("/settings/lookup/{$this->category->id}/values/{$value->id}/toggle")
            ->assertSessionHas('success');

        $this->assertTrue($value->fresh()->is_active);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_delete_non_system_value(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'deletable',
            'label_ms'    => 'Boleh Padam',
            'label_en'    => 'Deletable',
            'is_system'   => false,
        ]);

        $this->actingAs($this->user)
            ->delete("/settings/lookup/{$this->category->id}/values/{$value->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lookup_values', ['id' => $value->id]);
    }

    public function test_cannot_delete_system_value(): void
    {
        $value = LookupValue::create([
            'category_id' => $this->category->id,
            'code'        => 'system_val',
            'label_ms'    => 'Sistem',
            'label_en'    => 'System',
            'is_system'   => true,
        ]);

        $this->actingAs($this->user)
            ->delete("/settings/lookup/{$this->category->id}/values/{$value->id}")
            ->assertSessionHasErrors('lookup');

        $this->assertDatabaseHas('lookup_values', ['id' => $value->id]);
    }
}
