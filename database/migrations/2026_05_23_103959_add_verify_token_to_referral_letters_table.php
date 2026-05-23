<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referral_letters', function (Blueprint $table) {
            $table->string('verify_token', 64)->unique()->nullable()->after('relevant_history');
        });
    }

    public function down(): void
    {
        Schema::table('referral_letters', function (Blueprint $table) {
            $table->dropColumn('verify_token');
        });
    }
};
