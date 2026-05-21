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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('doctor_name');
            $table->date('visit_date');
            $table->string('chief_complaint', 500)->nullable();
            $table->string('status')->default('open');   // open / closed
            $table->text('soap_s')->nullable();          // Subjective
            $table->text('soap_o')->nullable();          // Objective
            $table->text('soap_a')->nullable();          // Assessment
            $table->text('soap_p')->nullable();          // Plan
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'visit_date']);
            $table->index('visit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
