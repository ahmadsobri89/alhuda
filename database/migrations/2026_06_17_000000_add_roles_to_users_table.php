<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('role');
        });

        // Backfill: roles = [role] untuk setiap pengguna sedia ada
        foreach (DB::table('users')->select('id', 'role')->get() as $u) {
            DB::table('users')->where('id', $u->id)->update([
                'roles' => json_encode($u->role ? [$u->role] : []),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
