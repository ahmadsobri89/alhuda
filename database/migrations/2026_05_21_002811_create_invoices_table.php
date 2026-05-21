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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->string('invoice_number', 20)->unique();
            $table->date('invoice_date');
            $table->string('status')->default('draft');      // draft/unpaid/paid/cancelled
            $table->string('payment_method')->nullable();    // cash/card/duitnow/panel/insurance
            $table->decimal('subtotal',      10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount',  10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'invoice_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
