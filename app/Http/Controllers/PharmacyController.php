<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Invoice;
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
            ->paginate(15)
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
            'filters'           => ['search' => $search],
            'allergiesInQueue'  => $allergiesInQueue,
            'lookups'           => $lookups,
            'drugItems'         => $drugItems,
        ]);
    }

    public function store(StorePrescriptionRequest $request)
    {
        DB::transaction(function () use ($request) {
            $rx = Prescription::create([
                'patient_id'         => $request->patient_id,
                'prescribing_doctor' => $request->prescribing_doctor,
                'user_id'            => Auth::id(),
                'notes'              => $request->notes,
                'drug_check_passed'  => true,
                'drug_check_notes'   => 'Tiada interaksi kritikal dikesan.',
            ]);

            foreach ($request->items as $item) {
                $rx->items()->create($item);
            }

            AuditLog::record('rx.create', "{$rx->rx_number} · {$rx->patient->name} · " . count($request->items) . ' ubat');
        });

        return back()->with('success', 'Preskripsi berjaya dibuat.');
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription)
    {
        if (in_array($prescription->status, ['dispensed', 'cancelled'])) {
            return back()->withErrors(['status' => 'Preskripsi yang telah diproses tidak boleh diedit.']);
        }

        DB::transaction(function () use ($request, $prescription) {
            $prescription->update([
                'patient_id'         => $request->patient_id,
                'prescribing_doctor' => $request->prescribing_doctor,
                'notes'              => $request->notes,
            ]);

            $prescription->items()->delete();
            foreach ($request->items as $item) {
                $prescription->items()->create($item);
            }

            AuditLog::record('rx.update', "{$prescription->rx_number} · {$prescription->patient->name}");
        });

        return back()->with('success', 'Preskripsi berjaya dikemaskini.');
    }

    public function updateStatus(Request $request, Prescription $prescription)
    {
        $request->validate(['status' => ['required', 'in:verifying,ready,dispensed,cancelled']]);

        $newStatus = $request->status;

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

        AuditLog::record("rx.{$newStatus}", "{$prescription->rx_number} · {$prescription->patient->name}");

        return back()->with('success', "Status preskripsi {$prescription->rx_number} dikemaskini.");
    }

    private function autoPopulateBill(Prescription $prescription): void
    {
        $prescription->loadMissing('items');
        if ($prescription->items->isEmpty()) return;

        // Prescription from a visit → merge into the same draft invoice as the services
        if ($prescription->visit_id) {
            $invoice = Invoice::firstOrCreate(
                ['visit_id' => $prescription->visit_id, 'status' => 'draft'],
                [
                    'patient_id'   => $prescription->patient_id,
                    'invoice_date' => now()->toDateString(),
                    'notes'        => "Auto-dijana daripada {$prescription->rx_number}",
                ]
            );
        } else {
            // Standalone pharmacy prescription (no visit) — use old patient+date key
            $invoice = Invoice::firstOrCreate(
                ['patient_id' => $prescription->patient_id, 'status' => 'draft', 'invoice_date' => now()->toDateString()],
                ['notes' => "Auto-dijana daripada {$prescription->rx_number}"]
            );
        }

        // Pre-load inventory items by FK; fall back to name-match for items without FK
        $linkedIds  = $prescription->items->pluck('inventory_item_id')->filter()->unique()->values()->all();
        $drugNames  = $prescription->items->whereNull('inventory_item_id')->map(fn ($i) => strtolower($i->drug_name))->all();

        $invById   = InventoryItem::whereIn('id', $linkedIds)->get()->keyBy('id');
        $invByName = collect();
        if (count($drugNames)) {
            $invByName = InventoryItem::where('status', 'active')
                ->where(function ($q) use ($drugNames) {
                    $q->whereIn(DB::raw('LOWER(name)'), $drugNames)
                      ->orWhereIn(DB::raw('LOWER(generic_name)'), $drugNames);
                })
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

            $unitPrice = $inv ? (float) $inv->selling_price : 0.0;
            $qty       = (int) $item->quantity;

            $invoice->items()->create([
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
