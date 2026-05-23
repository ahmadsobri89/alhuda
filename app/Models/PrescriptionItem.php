<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id', 'drug_name', 'kegunaan', 'drug_unit', 'dosage',
        'frequency', 'duration', 'quantity', 'instructions', 'item_note',
        'is_prn', 'complete_course',
    ];

    protected $casts = [
        'is_prn'          => 'boolean',
        'complete_course' => 'boolean',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
