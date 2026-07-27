<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Memo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'memo_number', 'patient_id', 'visit_id', 'issued_by',
        'issue_date', 'addressed_to', 'subject', 'nature',
        'content', 'notes', 'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Memo $memo) {
            if (! $memo->memo_number) {
                $year = (int) now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $memo->memo_number = sprintf('MEMO-%d-%04d', $year, $count);
            }

            if (! $memo->verify_token) {
                $memo->verify_token = Str::random(48);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at', 'verify_token'])
            ->useLogName('Memo');
    }
}
