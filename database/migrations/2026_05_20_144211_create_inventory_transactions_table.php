<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in','out','adjustment','disposal']);
            $table->integer('quantity_delta');   // signed: +in, -out/-disposal, ±adjustment
            $table->unsignedInteger('quantity_after'); // stock level after transaction
            $table->string('reference')->nullable();   // invoice no., Rx no., etc.
            $table->string('notes')->nullable();
            $table->string('performed_by');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
