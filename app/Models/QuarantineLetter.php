<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuarantineLetter extends Model
{
    protected $fillable = [
        'qn_number', 'patient_id', 'visit_id', 'issued_by',
        'issue_date', 'quarantine_start', 'quarantine_end', 'days',
        'diagnosis', 'reason', 'notes', 'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'       => 'date',
            'quarantine_start' => 'date',
            'quarantine_end'   => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuarantineLetter $qn) {
            if (! $qn->qn_number) {
                $year  = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $qn->qn_number = sprintf('QN-%d-%04d', $year, $count);
            }

            if (! $qn->verify_token) {
                $qn->verify_token = Str::random(48);
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
