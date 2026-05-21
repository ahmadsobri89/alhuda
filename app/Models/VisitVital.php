<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitVital extends Model
{
    protected $fillable = [
        'visit_id', 'bp_systolic', 'bp_diastolic', 'heart_rate',
        'temperature', 'spo2', 'weight', 'height',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
