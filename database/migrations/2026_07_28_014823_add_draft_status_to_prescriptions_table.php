<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE prescriptions MODIFY status ENUM('draft','pending','verifying','ready','dispensed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE prescriptions MODIFY status ENUM('pending','verifying','ready','dispensed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
