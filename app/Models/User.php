<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'mmc_number', 'mfa_enabled', 'status', 'google_id',
    ];

    /**
     * Display name used on documents/slips. Adds a "Dr." prefix for doctors,
     * unless the stored name already carries one.
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $name = trim((string) $this->name);
            if ($this->role === 'doctor' && $name !== '' && ! preg_match('/^dr\.?\s/i', $name)) {
                return "Dr. {$name}";
            }
            return $name;
        });
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'mfa_enabled'       => 'boolean',
        ];
    }
}
