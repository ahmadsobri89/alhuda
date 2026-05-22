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
        Schema::create('clinic_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Poliklinik Al-Huda');
            $table->string('tagline')->default('Klinik Perubatan Berdaftar');
            $table->string('reg_number')->nullable();
            $table->text('address')->default('No. 1, Jalan Al-Huda, Taman Harmoni');
            $table->string('postcode')->default('47500');
            $table->string('city')->default('Subang Jaya');
            $table->string('state')->default('Selangor');
            $table->string('phone')->default('03-8888 0000');
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_profiles');
    }
};
