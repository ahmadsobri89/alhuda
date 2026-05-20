<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'patient_id', 'name', 'ic_number', 'date_of_birth', 'gender',
        'phone', 'email', 'address', 'postcode', 'city', 'state',
        'blood_type', 'allergies', 'conditions',
        'emergency_contact_name', 'emergency_contact_phone',
        'visit_count', 'last_visit_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'last_visit_at' => 'datetime',
            'conditions'    => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (! $patient->patient_id) {
                $year  = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $patient->patient_id = sprintf('P-%d-%05d', $year, $count);
            }
        });
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    public function getAgeGenderAttribute(): string
    {
        $gender = $this->gender === 'male' ? 'L' : 'P';
        return $this->age . $gender;
    }
}
