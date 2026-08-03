<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('visit_id')->constrained()->nullOnDelete();
        });

        $this->promoteInvoiceLinks();
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }

    /**
     * Promote the already-established invoice_items.prescription_id links (from the
     * 2026_07_30_173717 backfill) onto a durable prescriptions.invoice_id column, so
     * future lookups don't depend on a per-item join that can be NULL/ambiguous.
     */
    private function promoteInvoiceLinks(): void
    {
        $prescriptions = DB::table('prescriptions')
            ->where('status', 'dispensed')
            ->whereNull('invoice_id')
            ->orderBy('id')
            ->get(['id']);

        foreach ($prescriptions as $rx) {
            $invoiceId = DB::table('invoice_items')
                ->where('prescription_id', $rx->id)
                ->value('invoice_id');

            if ($invoiceId) {
                DB::table('prescriptions')->where('id', $rx->id)->update(['invoice_id' => $invoiceId]);
            }
        }
    }
};
