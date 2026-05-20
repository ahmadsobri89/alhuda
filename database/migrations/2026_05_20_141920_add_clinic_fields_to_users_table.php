<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('receptionist')->after('name');
            $table->string('mmc_number')->nullable()->after('role');
            $table->boolean('mfa_enabled')->default(false)->after('mmc_number');
            $table->string('status')->default('active')->after('mfa_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'mmc_number', 'mfa_enabled', 'status']);
        });
    }
};
