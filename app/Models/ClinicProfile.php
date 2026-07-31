<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ClinicProfile extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'tagline', 'reg_number', 'ckaps_number',
        'address', 'postcode', 'city', 'state',
        'phone', 'fax', 'email', 'website', 'logo_path',
        'latitude', 'longitude', 'google_maps_url',
    ];

    public static function current(): static
    {
        return static::firstOrCreate(['id' => 1], [
            'name' => 'Poliklinik Al-Huda',
            'tagline' => 'Klinik Keluarga Pilihan Anda',
            'address' => '67-G, Jalan Wang Tepus, Pusat Bandar Barat',
            'postcode' => '06000',
            'city' => 'Jitra',
            'state' => 'Kedah',
            'phone' => '011-1668 1603',
            'latitude' => 6.236635,
            'longitude' => 100.4235227,
            'google_maps_url' => 'https://www.google.com/maps/dir//POLIKLINIK+AL+HUDA,+Pusat+Bandar+Barat,+67-G,+Taman+Mahsuri,+06000+Jitra,+Kedah/@3.104057,101.5361732,5084m/data=!3m1!1e3!4m8!4m7!1m0!1m5!1m1!1s0x304b5742502ee261:0x1cbd1ce9260e6cf9!2m2!1d100.4235227!2d6.236635',
        ]);
    }

    public function getWazeUrlAttribute(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return "https://waze.com/ul?ll={$this->latitude},{$this->longitude}&navigate=yes";
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return asset('logo.png');
    }

    public function getAddressFullAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->postcode.' '.$this->city,
            $this->state,
        ]));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->useLogName('ClinicProfile');
    }
}
