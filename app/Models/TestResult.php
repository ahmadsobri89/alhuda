<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TestResult extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tr_number', 'patient_id', 'visit_id', 'issued_by',
        'issue_date', 'specimen_received_date',
        'nationality', 'category_id',
        'facility_requestor', 'state', 'location_requestor',
        'requestor_name', 'facility_transit',
        'results', 'notes', 'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'specimen_received_date' => 'date',
            'results' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TestResult $tr) {
            if (! $tr->tr_number) {
                $year = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $tr->tr_number = sprintf('TR-%d-%04d', $year, $count);
            }

            if (! $tr->verify_token) {
                $tr->verify_token = Str::random(48);
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

    /**
     * Ringkasan baris ujian yang positif — untuk senarai & log.
     */
    public function getPositiveSummaryAttribute(): ?string
    {
        $positives = collect($this->results ?? [])
            ->filter(fn ($r) => Str::contains(Str::lower($r['result'] ?? ''), 'positi'))
            ->pluck('test_kit')
            ->filter()
            ->all();

        return $positives ? implode(', ', $positives) : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at', 'verify_token'])
            ->useLogName('TestResult');
    }
}
