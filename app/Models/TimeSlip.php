<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TimeSlip extends Model
{
    protected $fillable = [
        'slip_number', 'patient_id', 'visit_id', 'issued_by',
        'slip_date', 'arrival_time', 'departure_time', 'purpose', 'notes',
        'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'slip_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TimeSlip $slip) {
            if (! $slip->slip_number) {
                $year  = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $slip->slip_number = sprintf('TS-%d-%04d', $year, $count);
            }

            if (! $slip->verify_token) {
                $slip->verify_token = Str::random(48);
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

    public function durationMinutes(): int
    {
        [$ah, $am] = explode(':', $this->arrival_time);
        [$dh, $dm] = explode(':', $this->departure_time);
        return ($dh * 60 + $dm) - ($ah * 60 + $am);
    }
}
