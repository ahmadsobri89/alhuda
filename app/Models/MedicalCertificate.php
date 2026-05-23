<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MedicalCertificate extends Model
{
    protected $fillable = [
        'mc_number', 'patient_id', 'visit_id', 'issued_by',
        'issue_date', 'start_date', 'end_date', 'days',
        'diagnosis', 'notes', 'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MedicalCertificate $mc) {
            if (! $mc->mc_number) {
                $year  = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $mc->mc_number = sprintf('MC-%d-%04d', $year, $count);
            }

            if (! $mc->verify_token) {
                $mc->verify_token = Str::random(48);
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
}
