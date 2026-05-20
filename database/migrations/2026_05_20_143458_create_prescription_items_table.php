<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('dosage')->nullable();          // e.g. 500mg
            $table->string('frequency')->nullable();       // e.g. TDS - 3x sehari
            $table->string('duration')->nullable();        // e.g. 7 hari
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('instructions')->nullable();    // e.g. Selepas makan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
