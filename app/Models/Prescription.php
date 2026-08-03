<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Prescription extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'rx_number', 'patient_id', 'visit_id', 'invoice_id', 'prescribing_doctor', 'user_id',
        'status', 'notes', 'drug_check_passed', 'drug_check_notes',
        'dispensed_at', 'dispensed_by',
    ];

    protected function casts(): array
    {
        return [
            'dispensed_at' => 'datetime',
            'drug_check_passed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Prescription $rx) {
            if (! $rx->rx_number) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $rx->rx_number = sprintf('Rx-%d-%04d', $year, $count);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->useLogName('Prescription');
    }
}
