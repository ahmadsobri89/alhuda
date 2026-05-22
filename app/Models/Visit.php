<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'user_id', 'appointment_id', 'doctor_name',
        'visit_date', 'chief_complaint', 'status',
        'soap_s', 'soap_o', 'soap_a', 'soap_p',
        'signed_at', 'signed_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'signed_at'  => 'datetime',
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

    public function vitals(): HasOne
    {
        return $this->hasOne(VisitVital::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(VisitDiagnosis::class)->orderBy('type')->orderBy('id');
    }

    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class)->orderByDesc('id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(ReferralLetter::class)->orderByDesc('id');
    }

    public function timeSlips(): HasMany
    {
        return $this->hasMany(TimeSlip::class)->orderByDesc('id');
    }
}
