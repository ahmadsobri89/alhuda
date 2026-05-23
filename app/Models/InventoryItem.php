<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'generic_name', 'form', 'category', 'classification',
        'lot_number', 'expiry_date', 'supplier',
        'stock_quantity', 'reorder_level', 'unit_cost', 'selling_price', 'unit',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date'    => 'date',
            'unit_cost'      => 'decimal:2',
            'selling_price'  => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level'  => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->orderByDesc('created_at');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->lte(now()->addDays(90));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->lt(today());
    }

    public function getFlagsAttribute(): array
    {
        $flags = [];
        if ($this->is_low_stock) $flags[] = 'low';
        if ($this->is_expired)   $flags[] = 'expired';
        elseif ($this->is_expiring) $flags[] = 'expiring';
        if ($this->classification === 'poison_b') $flags[] = 'poison_b';
        if ($this->classification === 'poison_c') $flags[] = 'poison_c';
        if ($this->classification === 'controlled') $flags[] = 'controlled';
        return $flags;
    }

    public function getStockValueAttribute(): float
    {
        return round($this->stock_quantity * $this->unit_cost, 2);
    }
}
