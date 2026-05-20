<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('doctor_name');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('appointment_date');
            $table->string('appointment_time', 5);           // HH:MM
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('type')->default('follow_up');    // new/follow_up/annual_checkup/procedure/antenatal/teleconsult
            $table->string('reason')->nullable();            // chief complaint
            $table->string('status')->default('confirmed');  // confirmed/waiting/in_room/done/cancelled/no_show
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['appointment_date', 'appointment_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
