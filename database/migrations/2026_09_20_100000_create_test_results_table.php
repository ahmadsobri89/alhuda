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
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->string('tr_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issued_by');
            $table->date('issue_date');
            $table->date('specimen_received_date');
            // Maklumat pesakit — bebas taip, lalai diisi dari rekod pesakit
            $table->string('nationality')->nullable();
            $table->string('category_id')->nullable();
            // Maklumat fasiliti pemohon — bebas taip, lalai dari profil klinik
            $table->string('facility_requestor')->nullable();
            $table->string('state')->nullable();
            $table->string('location_requestor', 500)->nullable();
            $table->string('requestor_name')->nullable();
            $table->string('facility_transit')->nullable();
            // Baris ujian: [{ test_date, test_kit, result }]
            $table->json('results');
            $table->string('notes', 500)->nullable();
            $table->string('verify_token', 64)->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
