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
        'name', 'email', 'password', 'role', 'roles', 'mmc_number', 'mfa_enabled', 'status', 'google_id',
    ];

    /**
     * Display name used on documents/slips. Adds a "Dr." prefix for doctors,
     * unless the stored name already carries one.
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $name = trim((string) $this->name);
            if ($this->hasRole('doctor') && $name !== '' && ! preg_match('/^dr\.?\s/i', $name)) {
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
            'roles'             => 'array',
        ];
    }

    // ── Peranan berbilang ────────────────────────────────────────────────

    /** Senarai peranan; fallback ke lajur `role` tunggal jika kosong. */
    public function rolesList(): array
    {
        $roles = $this->roles;
        if (is_array($roles) && count($roles) > 0) {
            return $roles;
        }
        return $this->role ? [$this->role] : [];
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->rolesList(), true);
    }

    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->rolesList())) > 0;
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }
        $allowed = config("access.modules.{$module}");
        if ($allowed === null) {
            return true; // modul tak dikategori → tidak disekat
        }
        return in_array('*', $allowed, true) || $this->hasAnyRole($allowed);
    }

    /** Senarai modul yang pengguna ini boleh akses (untuk gating menu). */
    public function accessibleModules(): array
    {
        return array_values(array_filter(
            array_keys(config('access.modules', [])),
            fn ($m) => $this->canAccessModule($m)
        ));
    }
}
