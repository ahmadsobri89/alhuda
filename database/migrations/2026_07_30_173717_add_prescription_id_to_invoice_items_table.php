<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('prescription_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });

        $this->backfillPrescriptionLinks();
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prescription_id');
        });
    }

    /**
     * Best-effort link of pre-existing drug invoice_items back to the prescription
     * that generated them, so future edits to already-dispensed prescriptions can
     * find and resync the correct invoice. Only links unambiguous 1:1 matches.
     */
    private function backfillPrescriptionLinks(): void
    {
        $prescriptions = DB::table('prescriptions')->where('status', 'dispensed')->get();

        foreach ($prescriptions as $rx) {
            $invoiceQuery = DB::table('invoices');
            if ($rx->visit_id) {
                $invoiceQuery->where('visit_id', $rx->visit_id);
            } else {
                $invoiceQuery->where('patient_id', $rx->patient_id)
                    ->whereDate('invoice_date', substr($rx->created_at, 0, 10));
            }
            $invoice = $invoiceQuery->first();
            if (! $invoice) {
                continue;
            }

            $items = DB::table('prescription_items')->where('prescription_id', $rx->id)->get();

            foreach ($items as $item) {
                $candidates = DB::table('invoice_items')
                    ->where('invoice_id', $invoice->id)
                    ->whereNull('prescription_id')
                    ->where('description', $item->drug_name)
                    ->where('quantity', $item->quantity)
                    ->pluck('id');

                if ($candidates->count() === 1) {
                    DB::table('invoice_items')
                        ->where('id', $candidates->first())
                        ->update(['prescription_id' => $rx->id]);
                }
            }
        }
    }
};
