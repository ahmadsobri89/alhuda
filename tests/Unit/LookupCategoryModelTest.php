<?php

namespace Tests\Unit;

use App\Models\LookupCategory;
use App\Models\LookupValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    private LookupCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = LookupCategory::create([
            'group'      => 'patient',
            'slug'       => 'test_cat',
            'name_ms'    => 'Ujian',
            'name_en'    => 'Test',
            'sort_order' => 1,
        ]);
    }

    private function makeValue(array $overrides = []): LookupValue
    {
        return LookupValue::create(array_merge([
            'category_id' => $this->category->id,
            'code'        => uniqid('code_'),
            'label_ms'    => 'Label BM',
            'label_en'    => 'Label EN',
            'is_active'   => true,
            'sort_order'  => 1,
        ], $overrides));
    }

    // ── forSlug ───────────────────────────────────────────────────────────────

    public function test_for_slug_returns_empty_array_when_slug_not_found(): void
    {
        $result = LookupCategory::forSlug('nonexistent_slug');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_for_slug_returns_active_values_only(): void
    {
        $this->makeValue(['code' => 'active_one', 'label_ms' => 'Aktif', 'label_en' => 'Active', 'is_active' => true]);
        $this->makeValue(['code' => 'inactive_one', 'label_ms' => 'Tidak Aktif', 'label_en' => 'Inactive', 'is_active' => false]);

        $result = LookupCategory::forSlug('test_cat');

        $this->assertCount(1, $result);
        $this->assertEquals('active_one', $result[0]['code']);
    }

    public function test_for_slug_returns_code_label_ms_label_en(): void
    {
        $this->makeValue([
            'code'     => 'mycode',
            'label_ms' => 'Label BM',
            'label_en' => 'Label EN',
            'is_active' => true,
        ]);

        $result = LookupCategory::forSlug('test_cat');

        $this->assertArrayHasKey('code', $result[0]);
        $this->assertArrayHasKey('label_ms', $result[0]);
        $this->assertArrayHasKey('label_en', $result[0]);
        $this->assertEquals('mycode', $result[0]['code']);
        $this->assertEquals('Label BM', $result[0]['label_ms']);
        $this->assertEquals('Label EN', $result[0]['label_en']);
    }

    public function test_for_slug_returns_values_ordered_by_sort_order(): void
    {
        $this->makeValue(['code' => 'third',  'sort_order' => 3, 'is_active' => true]);
        $this->makeValue(['code' => 'first',  'sort_order' => 1, 'is_active' => true]);
        $this->makeValue(['code' => 'second', 'sort_order' => 2, 'is_active' => true]);

        $result = LookupCategory::forSlug('test_cat');

        $this->assertEquals('first',  $result[0]['code']);
        $this->assertEquals('second', $result[1]['code']);
        $this->assertEquals('third',  $result[2]['code']);
    }

    // ── forSlugs ──────────────────────────────────────────────────────────────

    public function test_for_slugs_returns_keyed_array_by_slug(): void
    {
        $cat2 = LookupCategory::create([
            'group'      => 'patient',
            'slug'       => 'other_cat',
            'name_ms'    => 'Lain',
            'name_en'    => 'Other',
            'sort_order' => 2,
        ]);

        $this->makeValue(['code' => 'val1', 'is_active' => true]);
        LookupValue::create([
            'category_id' => $cat2->id,
            'code'        => 'val2',
            'label_ms'    => 'Val',
            'label_en'    => 'Val',
            'is_active'   => true,
        ]);

        $result = LookupCategory::forSlugs(['test_cat', 'other_cat']);

        $this->assertArrayHasKey('test_cat', $result);
        $this->assertArrayHasKey('other_cat', $result);
        $this->assertEquals('val1', $result['test_cat'][0]['code']);
        $this->assertEquals('val2', $result['other_cat'][0]['code']);
    }

    public function test_for_slugs_returns_empty_array_for_missing_slug(): void
    {
        $result = LookupCategory::forSlugs(['test_cat', 'missing_slug']);

        $this->assertArrayHasKey('missing_slug', $result);
        $this->assertEmpty($result['missing_slug']);
    }

    public function test_for_slugs_with_empty_array_returns_empty_result(): void
    {
        $result = LookupCategory::forSlugs([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── values() relationship ─────────────────────────────────────────────────

    public function test_values_relationship_returns_all_values(): void
    {
        $this->makeValue(['code' => 'a', 'is_active' => true]);
        $this->makeValue(['code' => 'b', 'is_active' => false]);

        $this->assertCount(2, $this->category->values);
    }

    public function test_values_are_ordered_by_sort_order(): void
    {
        $this->makeValue(['code' => 'z', 'sort_order' => 10, 'is_active' => true]);
        $this->makeValue(['code' => 'a', 'sort_order' => 1,  'is_active' => true]);

        $codes = $this->category->values->pluck('code')->toArray();
        $this->assertEquals(['a', 'z'], $codes);
    }

    // ── activeValues() relationship ───────────────────────────────────────────

    public function test_active_values_returns_only_is_active_true(): void
    {
        $this->makeValue(['code' => 'on',  'is_active' => true]);
        $this->makeValue(['code' => 'off', 'is_active' => false]);

        $active = $this->category->activeValues;

        $this->assertCount(1, $active);
        $this->assertEquals('on', $active->first()->code);
    }

    public function test_active_values_are_ordered_by_sort_order(): void
    {
        $this->makeValue(['code' => 'second', 'sort_order' => 2, 'is_active' => true]);
        $this->makeValue(['code' => 'first',  'sort_order' => 1, 'is_active' => true]);

        $codes = $this->category->activeValues->pluck('code')->toArray();
        $this->assertEquals(['first', 'second'], $codes);
    }
}
