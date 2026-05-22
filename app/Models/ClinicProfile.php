<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicProfile extends Model
{
    protected $fillable = [
        'name', 'tagline', 'reg_number',
        'address', 'postcode', 'city', 'state',
        'phone', 'fax', 'email', 'website', 'logo_path',
    ];

    public static function current(): static
    {
        return static::firstOrCreate(['id' => 1], [
            'name'    => 'Poliklinik Al-Huda',
            'tagline' => 'Klinik Perubatan Berdaftar',
            'address' => 'No. 1, Jalan Al-Huda, Taman Harmoni',
            'postcode' => '47500',
            'city'    => 'Subang Jaya',
            'state'   => 'Selangor',
            'phone'   => '03-8888 0000',
        ]);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        return asset('logo.png');
    }

    public function getAddressFullAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->postcode . ' ' . $this->city,
            $this->state,
        ]));
    }
}
