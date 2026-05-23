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
        Schema::create('quarantine_letters', function (Blueprint $table) {
            $table->id();
            $table->string('qn_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issued_by');
            $table->date('issue_date');
            $table->date('quarantine_start');
            $table->date('quarantine_end');
            $table->unsignedSmallInteger('days');
            $table->string('diagnosis')->nullable();
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('quarantine_letters');
    }
};
