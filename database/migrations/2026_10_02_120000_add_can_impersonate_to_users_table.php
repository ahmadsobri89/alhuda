<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kebenaran impersonate ialah bendera berasingan (bukan peranan) supaya
     * ia tidak bergantung pada peranan 'admin'. Default false untuk SEMUA
     * pengguna — beri melalui `php artisan user:impersonator {email}`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_impersonate')->default(false)->after('roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_impersonate');
        });
    }
};
