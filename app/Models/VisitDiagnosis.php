<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDiagnosis extends Model
{
    protected $fillable = ['visit_id', 'icd_code', 'description', 'type'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
