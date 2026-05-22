<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LookupCategory extends Model
{
    protected $fillable = [
        'group', 'slug', 'name_ms', 'name_en',
        'description_ms', 'description_en', 'sort_order',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class, 'category_id')->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->hasMany(LookupValue::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /** Return active values for a slug as [{code, label_ms, label_en}] */
    public static function forSlug(string $slug): array
    {
        $cat = static::where('slug', $slug)->first();
        if (!$cat) return [];

        return $cat->activeValues()
            ->get(['code', 'label_ms', 'label_en'])
            ->map(fn ($v) => [
                'code'     => $v->code,
                'label_ms' => $v->label_ms,
                'label_en' => $v->label_en,
            ])
            ->toArray();
    }

    /** Return active values for multiple slugs keyed by slug */
    public static function forSlugs(array $slugs): array
    {
        return collect($slugs)->mapWithKeys(
            fn ($slug) => [$slug => static::forSlug($slug)]
        )->toArray();
    }
}
