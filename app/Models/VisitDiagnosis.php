<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class VisitDiagnosis extends Model
{
    use LogsActivity;

    protected $fillable = ['visit_id', 'icd_code', 'description', 'type'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->useLogName('VisitDiagnosis');
    }
}
