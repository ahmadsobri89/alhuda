<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReferralLetter extends Model
{
    protected $fillable = [
        'ref_number', 'patient_id', 'visit_id', 'issued_by',
        'issue_date', 'referred_to', 'referred_to_dept',
        'urgency', 'reason', 'clinical_summary', 'relevant_history',
        'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReferralLetter $ref) {
            if (! $ref->ref_number) {
                $year  = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $ref->ref_number = sprintf('REF-%d-%04d', $year, $count);
            }

            if (! $ref->verify_token) {
                $ref->verify_token = Str::random(48);
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
