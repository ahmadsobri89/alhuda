<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id', 'doctor_name', 'user_id',
        'appointment_date', 'appointment_time', 'duration_minutes',
        'type', 'reason', 'status', 'notes', 'consent_pdpa',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'consent_pdpa' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->useLogName('Appointment');
    }
}
