<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mohdafiez7@gmail.com'],
            [
                'name' => 'MOHD AFIEZ',
                'role' => 'admin',
                'roles' => ['admin'],
                'mmc_number' => null,
                'mfa_enabled' => true,
                'status' => 'active',
                'password' => Hash::make('!Haris1010'),
                'email_verified_at' => now(),
            ]
        );
    }
}
