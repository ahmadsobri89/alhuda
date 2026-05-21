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
        Schema::create('referral_letters', function (Blueprint $table) {
            $table->id();
            $table->string('ref_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issued_by');
            $table->date('issue_date');
            $table->string('referred_to');
            $table->string('referred_to_dept')->nullable();
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('reason');
            $table->text('clinical_summary')->nullable();
            $table->text('relevant_history')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_letters');
    }
};
