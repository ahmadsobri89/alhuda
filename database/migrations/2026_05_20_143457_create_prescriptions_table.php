<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('rx_number')->unique();                          // Rx-2026-1001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('prescribing_doctor');                           // free-text name
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // prescriber user
            $table->enum('status', ['pending','verifying','ready','dispensed','cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('drug_check_notes')->nullable();
            $table->boolean('drug_check_passed')->default(true);
            $table->timestamp('dispensed_at')->nullable();
            $table->string('dispensed_by')->nullable();                     // pharmacist name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
