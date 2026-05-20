<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                // Amoxicillin 500mg
            $table->string('generic_name')->nullable();            // Amoxicillin
            $table->string('form');                                // Tablet, Capsule, MDI…
            $table->string('category')->nullable();               // Antibiotik, Analgesik…
            $table->enum('classification', ['general','poison_b','poison_c','controlled'])->default('general');
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(50);
            $table->decimal('unit_cost', 10, 2)->default(0.00);   // cost per unit
            $table->string('unit')->default('unit');              // tablet, capsule, mL, puff…
            $table->text('notes')->nullable();
            $table->string('status')->default('active');          // active, discontinued
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
