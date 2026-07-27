<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PrescriptionItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'prescription_id', 'inventory_item_id', 'drug_name', 'kegunaan', 'drug_unit', 'dosage',
        'frequency', 'duration', 'quantity', 'unit_price', 'instructions', 'item_note',
        'is_prn', 'complete_course',
    ];

    protected $casts = [
        'is_prn' => 'boolean',
        'complete_course' => 'boolean',
        'unit_price' => 'float',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->useLogName('PrescriptionItem');
    }
}
