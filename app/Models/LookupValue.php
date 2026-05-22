<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookupValue extends Model
{
    protected $fillable = [
        'category_id', 'code', 'label_ms', 'label_en',
        'sort_order', 'is_active', 'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LookupCategory::class, 'category_id');
    }
}
