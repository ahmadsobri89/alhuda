<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LookupCategory;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PharmacyController extends Controller
{
    private function formatRx(Prescription $rx): array
    {
        return [
            'id'                 => $rx->id,
            'rx_number'          => $rx->rx_number,
            'patient_id'         => $rx->patient_id,
            'patient_name'       => $rx->patient->name,
            'patient_ic'         => $rx->patient->ic_number,
            'patient_allergies'  => $rx->patient->allergies,
            'prescribing_doctor' => $rx->prescribing_doctor,
            'status'             => $rx->status,
            'notes'              => $rx->notes,
            'drug_check_passed'  => $rx->drug_check_passed,
            'drug_check_notes'   => $rx->drug_check_notes,
            'dispensed_at'       => $rx->dispensed_at?->format('d/m/Y H:i'),
            'dispensed_by'       => $rx->dispensed_by,
            'wait_time'          => $rx->created_at->diffForHumans(),
            'created_at'         => $rx->created_at->format('d/m/Y H:i'),
            'items'              => $rx->items->map(fn ($item) => [
                'id'                  => $item->id,
                'inventory_item_id'   => $item->inventory_item_id,
                'drug_name'           => $item->drug_name,
                'kegunaan'            => $item->kegunaan,
                'drug_unit'           => $item->drug_unit,
                'dosage'              => $item->dosage,
                'frequency'           => $item->frequency,
                'duration'            => $item->duration,
                'quantity'            => $item->quantity,
                'unit_price'          => $item->unit_price,
                'instructions'        => $item->instructions,
                'item_note'           => $item->item_note,
                'is_prn'              => $item->is_prn,
                'complete_course'     => $item->complete_course,
            ])->all(),
        ];
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 30, 50, 100], true)) {
            $perPage = 15;
        }

        $queueStatuses = ['pending', 'verifying', 'ready'];

        $queue = Prescription::with(['patient', 'items'])
            ->whereIn('status', $queueStatuses)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($rx) => $this->formatRx($rx));

        $history = Prescription::with(['patient', 'items'])
            ->whereIn('status', ['dispensed', 'cancelled'])
            ->when($search, fn ($q) =>
                $q->where(function ($q2) use ($search) {
                    $q2->where('rx_number', 'like', "%{$search}%")
                       ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%"));
                })
            )
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($rx) => $this->formatRx($rx));

        $patients = Patient::orderBy('name')
            ->get(['id', 'name', 'ic_number', 'patient_id', 'allergies', 'conditions']);

        // Drug-check summary for queue
        $allergiesInQueue = $queue->pluck('patient_allergies')->filter()->unique()->values();

        $lookups = LookupCategory::forSlugs(['kekerapan_dos', 'arahan_dos', 'bentuk_ubat']);

        $drugItems = InventoryItem::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'form', 'unit', 'selling_price', 'stock_quantity', 'classification']);

        return Inertia::render('Pharmacy', [
            'currentRoute'      => 'pharmacy',
            'queue'             => $queue,
            'history'           => $history,
            'patients'          => $patients,
            'filters'           => ['search' => $search, 'per_page' => $perPage],
            'allergiesInQueue'  => $allergiesInQueue,
            'lookups'           => $lookups,
            'drugItems'         => $drugItems,
        ]);
    }

    public function store(StorePrescriptionRequest $request)
    {
        $isOtc = $request->boolean('is_otc');

        DB::transaction(function () use ($request, $isOtc) {
            $rx = Prescription::create([
                'patient_id'         => $request->patient_id,
                'prescribing_doctor' => $request->prescribing_doctor ?: 'Jualan Terus (Tanpa Preskripsi Doktor)',
                'user_id'            => Auth::id(),
                'notes'              => $request->notes,
                'drug_check_passed'  => true,
                'drug_check_notes'   => 'Tiada interaksi kritikal dikesan.',
                ...($isOtc ? [
                    'status'       => 'dispensed',
                    'dispensed_at' => now(),
                    'dispensed_by' => Auth::user()?->name ?? 'Pharmacist',
                ] : []),
            ]);

            foreach ($request->items as $item) {
                $rx->items()->create($item);
            }

            AuditLog::record('rx.create', "{$rx->rx_number} · {$rx->patient->name} · " . count($request->items) . ' ubat');

            if ($isOtc) {
                $this->autoPopulateBill($rx);
                AuditLog::record('rx.dispensed', "{$rx->rx_number} · {$rx->patient->name}");
            }
        });

        return back()->with('success', 'Preskripsi berjaya dibuat.');
    }

    public function quickCreatePatient(StorePatientRequest $request)
    {
        $patient = Patient::create($request->validated());
        AuditLog::record('patient.create', "{$patient->patient_id} · {$patient->name}");

        return back()->with('success', "Pesakit {$patient->name} berjaya didaftarkan.")
            ->with('quickPatientId', $patient->id);
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription)
    {
        if ($prescription->status === 'cancelled') {
            return back()->withErrors(['status' => 'Preskripsi yang telah dibatalkan tidak boleh diedit.']);
        }

        try {
            DB::transaction(function () use ($request, $prescription) {
                $rx = Prescription::whereKey($prescription->id)->lockForUpdate()->firstOrFail();
                $wasDispensed = $rx->status === 'dispensed';
                $invoice = null;

                if ($wasDispensed) {
                    $invoiceId = InvoiceItem::where('prescription_id', $rx->id)->value('invoice_id');
                    if ($invoiceId) {
                        $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->first();
                        if ($invoice && in_array($invoice->status, ['paid', 'cancelled'])) {
                            $reason = $invoice->status === 'paid' ? 'telah dibayar' : 'telah dibatalkan';
                            throw new \RuntimeException("Invois {$invoice->invoice_number} {$reason}. Ubat untuk preskripsi ini tidak boleh diubah lagi.");
                        }
                    }
                }

                $rx->update([
                    'patient_id'         => $wasDispensed ? $rx->patient_id : $request->patient_id,
                    'prescribing_doctor' => $wasDispensed ? $rx->prescribing_doctor : $request->prescribing_doctor,
                    'notes'              => $request->notes,
                ]);

                if ($wasDispensed && $invoice) {
                    $this->reverseDispenseEffects($rx, $invoice);
                }

                $oldItemsLog = $rx->items->map(fn ($i) => "{$i->drug_name} ×{$i->quantity}")->all();

                $rx->items()->delete();
                foreach ($request->items as $item) {
                    $rx->items()->create($item);
                }

                if ($wasDispensed) {
                    $this->autoPopulateBill($rx, $invoice);
                }

                $newItemsLog = collect($request->items)->map(fn ($i) => "{$i['drug_name']} ×{$i['quantity']}")->all();

                AuditLog::record(
                    $wasDispensed ? 'rx.update_dispensed' : 'rx.update',
                    "{$rx->rx_number} · {$rx->patient->name}",
                    true,
                    ['ubat_lama' => $oldItemsLog, 'ubat_baru' => $newItemsLog, 'resynced' => $wasDispensed]
                );
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Preskripsi berjaya dikemaskini.');
    }

    public function updateStatus(Request $request, Prescription $prescription)
    {
        $request->validate(['status' => ['required', 'in:verifying,ready,dispensed,cancelled']]);

        $newStatus = $request->status;

        DB::transaction(function () use ($prescription, $newStatus) {
            $prescription->update(array_merge(
                ['status' => $newStatus],
                $newStatus === 'dispensed' ? [
                    'dispensed_at' => now(),
                    'dispensed_by' => Auth::user()?->name ?? 'Pharmacist',
                ] : []
            ));

            if ($newStatus === 'dispensed' && $prescription->wasChanged('status')) {
                $this->autoPopulateBill($prescription);
            }
        });

        AuditLog::record("rx.{$newStatus}", "{$prescription->rx_number} · {$prescription->patient->name}");

        return back()->with('success', "Status preskripsi {$prescription->rx_number} dikemaskini.");
    }

    private function resolveInvoiceForPrescription(Prescription $prescription): Invoice
    {
        // Prescription from a visit → merge into the same draft invoice as the services
        if ($prescription->visit_id) {
            return Invoice::firstOrCreate(
                ['visit_id' => $prescription->visit_id, 'status' => 'draft'],
                [
                    'patient_id'   => $prescription->patient_id,
                    'invoice_date' => now()->toDateString(),
                    'notes'        => "Auto-dijana daripada {$prescription->rx_number}",
                ]
            );
        }

        // Standalone pharmacy prescription (no visit) — use old patient+date key
        return Invoice::firstOrCreate(
            ['patient_id' => $prescription->patient_id, 'status' => 'draft', 'invoice_date' => now()->toDateString()],
            ['notes' => "Auto-dijana daripada {$prescription->rx_number}"]
        );
    }

    /**
     * Reverse the invoice items and stock deduction a previous dispense/resync created for
     * this prescription, so the edited item list can be re-billed and re-deducted cleanly.
     * Stock is restored from the InventoryTransaction ledger (not the PrescriptionItem
     * snapshot) so it reflects what was actually deducted, even if that deduction was
     * clamped by insufficient stock.
     */
    private function reverseDispenseEffects(Prescription $prescription, Invoice $invoice): void
    {
        $actor = Auth::user()?->name ?? 'System';

        InvoiceItem::where('prescription_id', $prescription->id)->delete();

        $netByItem = InventoryTransaction::where('reference', $prescription->rx_number)
            ->selectRaw('inventory_item_id, SUM(quantity_delta) as net')
            ->groupBy('inventory_item_id')
            ->havingRaw('SUM(quantity_delta) != 0')
            ->pluck('net', 'inventory_item_id');

        foreach ($netByItem as $inventoryItemId => $net) {
            $inv = InventoryItem::whereKey($inventoryItemId)->lockForUpdate()->first();
            if (! $inv) continue;

            $restore = -$net;
            $newStock = max(0, $inv->stock_quantity + $restore);
            $inv->update(['stock_quantity' => $newStock]);
            $inv->transactions()->create([
                'type'           => 'adjustment',
                'quantity_delta' => $restore,
                'quantity_after' => $newStock,
                'reference'      => $prescription->rx_number,
                'notes'          => "Diedit selepas dispense — stok diselaraskan semula",
                'performed_by'   => $actor,
            ]);
        }
    }

    private function autoPopulateBill(Prescription $prescription, ?Invoice $invoice = null): void
    {
        // Always reload — a caller may have deleted/recreated items on this same
        // instance earlier in the request, which leaves a stale cached relation.
        $prescription->load('items');
        if ($prescription->items->isEmpty()) return;

        $invoice ??= $this->resolveInvoiceForPrescription($prescription);

        // Pre-load inventory items by FK; fall back to name-match for items without FK
        $linkedIds  = $prescription->items->pluck('inventory_item_id')->filter()->unique()->values()->all();
        $drugNames  = $prescription->items->whereNull('inventory_item_id')->map(fn ($i) => strtolower($i->drug_name))->all();

        $invById   = InventoryItem::whereIn('id', $linkedIds)->lockForUpdate()->get()->keyBy('id');
        $invByName = collect();
        if (count($drugNames)) {
            $invByName = InventoryItem::where('status', 'active')
                ->where(function ($q) use ($drugNames) {
                    $q->whereIn(DB::raw('LOWER(name)'), $drugNames)
                      ->orWhereIn(DB::raw('LOWER(generic_name)'), $drugNames);
                })
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($i) => strtolower($i->name));
        }

        $actor = Auth::user()?->name ?? 'System';

        foreach ($prescription->items as $item) {
            // Resolve inventory item: by FK first, then by name
            $inv = $item->inventory_item_id
                ? ($invById[$item->inventory_item_id] ?? null)
                : ($invByName[strtolower($item->drug_name)]
                   ?? $invByName->first(fn ($i) => strtolower($i->generic_name) === strtolower($item->drug_name)));

            $unitPrice = $item->unit_price !== null
                ? (float) $item->unit_price
                : ($inv ? (float) $inv->selling_price : 0.0);
            $qty       = (int) $item->quantity;

            $invoice->items()->create([
                'prescription_id' => $prescription->id,
                'type'        => 'drug',
                'code'        => null,
                'description' => $item->drug_name,
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'total_price' => round($qty * $unitPrice, 2),
            ]);

            // Deduct stock when an inventory item is linked
            if ($inv && $qty > 0) {
                $newStock = max(0, $inv->stock_quantity - $qty);
                $inv->update(['stock_quantity' => $newStock]);
                $inv->transactions()->create([
                    'type'           => 'out',
                    'quantity_delta' => -$qty,
                    'quantity_after' => $newStock,
                    'reference'      => $prescription->rx_number,
                    'notes'          => "Dikeluarkan melalui preskripsi {$prescription->rx_number}",
                    'performed_by'   => $actor,
                ]);
            }
        }

        $invoice->recalc();
        AuditLog::record('billing.auto_rx', "{$invoice->invoice_number} ← {$prescription->rx_number}");
    }

    public function destroy(Prescription $prescription)
    {
        $rxNum = $prescription->rx_number;
        $prescription->delete();
        AuditLog::record('rx.delete', $rxNum);

        return back()->with('success', "Preskripsi {$rxNum} dipadam.");
    }

    public function print(Prescription $prescription)
    {
        $prescription->load(['patient', 'items']);
        AuditLog::record('rx.print', "{$prescription->rx_number} · {$prescription->patient->name}");
        return view('pharmacy.print', ['rx' => $prescription]);
    }

    public function label(Prescription $prescription)
    {
        $prescription->load(['patient', 'items']);
        AuditLog::record('rx.label', "{$prescription->rx_number} · {$prescription->patient->name}");

        $lookups = LookupCategory::forSlugs(['kekerapan_dos', 'arahan_dos', 'bentuk_ubat']);

        return view('pharmacy.label', [
            'rx'      => $prescription,
            'lookups' => $lookups,
        ]);
    }
}
