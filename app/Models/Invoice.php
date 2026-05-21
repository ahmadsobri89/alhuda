<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'visit_id', 'invoice_number', 'invoice_date',
        'status', 'payment_method',
        'subtotal', 'discount_amount', 'total_amount',
        'paid_at', 'paid_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'    => 'date',
            'paid_at'         => 'datetime',
            'subtotal'        => 'float',
            'discount_amount' => 'float',
            'total_amount'    => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $inv) {
            if (! $inv->invoice_number) {
                $year  = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $inv->invoice_number = sprintf('INV-%d-%06d', $year, $count);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function recalc(): void
    {
        $subtotal = round($this->items()->sum('total_price'), 2);
        $total    = round($subtotal - ($this->discount_amount ?? 0), 2);
        $this->update(['subtotal' => $subtotal, 'total_amount' => $total]);
    }
}
