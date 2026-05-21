<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'type', 'code', 'description',
        'quantity', 'unit_price', 'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'float',
            'unit_price'  => 'float',
            'total_price' => 'float',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
