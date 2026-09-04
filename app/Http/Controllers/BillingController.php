<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LookupCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Service;
use App\Services\TaskNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('patient')
            ->where('status', '!=', 'emr_draft')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn ($q) =>
                $q->where('invoice_number', 'like', $s)
                  ->orWhereHas('patient', fn ($pq) =>
                      $pq->where('name', 'like', $s)->orWhere('ic_number', 'like', $s)
                  )
            );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 30);
        if (! in_array($perPage, [15, 30, 50, 100], true)) {
            $perPage = 30;
        }

        $invoices = $query->paginate($perPage)->onEachSide(0)->withQueryString()
            ->through(fn ($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'patient_name'   => $inv->patient->name,
                'patient_ic'     => $inv->patient->ic_number,
                'invoice_date'   => $inv->invoice_date->format('Y-m-d'),
                'status'         => $inv->status,
                'payment_method' => $inv->payment_method,
                'total_amount'   => $inv->total_amount,
            ]);

        $selected = null;
        if ($request->filled('invoice')) {
            $inv = Invoice::with(['patient', 'items'])->find($request->invoice);
            if ($inv) $selected = $this->formatInvoice($inv);
        }

        $today = now()->format('Y-m-d');
        $month = now()->month;
        $year  = now()->year;

        $stats = [
            'today_revenue'      => Invoice::where('status', 'paid')->whereDate('paid_at', $today)->sum('total_amount'),
            'month_collected'    => Invoice::where('status', 'paid')->whereMonth('paid_at', $month)->whereYear('paid_at', $year)->sum('total_amount'),
            'outstanding_count'  => Invoice::whereIn('status', ['draft', 'unpaid'])->count(),
            'outstanding_amount' => Invoice::whereIn('status', ['draft', 'unpaid'])->sum('total_amount'),
        ];

        $patients = Patient::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'ic_number', 'patient_id']);

        $lookups = LookupCategory::forSlugs(['kaedah_bayaran', 'jenis_item_bil']);

        $drugItems = InventoryItem::where('status', 'active')
            ->where('selling_price', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'form', 'unit', 'selling_price', 'stock_quantity']);

        $serviceItems = Service::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category', 'price']);

        return Inertia::render('Billing', [
            'currentRoute' => 'billing',
            'invoices'     => $invoices,
            'selected'     => $selected,
            'patients'     => $patients,
            'stats'        => $stats,
            'filters'      => array_merge($request->only(['search', 'status', 'invoice']), ['per_page' => $perPage]),
            'today'        => $today,
            'lookups'      => $lookups,
            'drugItems'    => $drugItems,
            'serviceItems' => $serviceItems,
        ]);
    }

    private function formatInvoice(Invoice $inv): array
    {
        return [
            'id'              => $inv->id,
            'invoice_number'  => $inv->invoice_number,
            'patient_id'      => $inv->patient_id,
            'patient_name'    => $inv->patient->name,
            'patient_ic'      => $inv->patient->ic_number,
            'patient_id_str'  => $inv->patient->patient_id,
            'invoice_date'    => $inv->invoice_date->format('Y-m-d'),
            'status'          => $inv->status,
            'payment_method'  => $inv->payment_method,
            'subtotal'        => round($inv->subtotal, 2),
            'discount_amount' => round($inv->discount_amount, 2),
            'total_amount'    => round($inv->total_amount, 2),
            'paid_at'         => $inv->paid_at?->format('d/m/Y H:i'),
            'paid_by'         => $inv->paid_by,
            'notes'           => $inv->notes,
            'items'           => $inv->items->map(fn ($i) => [
                'id'          => $i->id,
                'type'        => $i->type,
                'code'        => $i->code,
                'description' => $i->description,
                'quantity'    => $i->quantity,
                'unit_price'  => round($i->unit_price, 2),
                'total_price' => round($i->total_price, 2),
            ])->values()->toArray(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'   => ['required', 'exists:patients,id'],
            'invoice_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $inv = Invoice::create(array_merge($data, ['status' => 'draft']));

        AuditLog::record('billing.create', "{$inv->patient->name} · {$inv->invoice_number}");

        return redirect()->route('billing', ['invoice' => $inv->id])
            ->with('success', "Invois {$inv->invoice_number} dicipta.");
    }

    /**
     * Draft/unpaid invoices are freely editable. A paid invoice may still have
     * its items adjusted (e.g. billing correction), but only with a mandatory
     * reason, mirroring updatePaymentMethod()'s post-payment edit trail.
     */
    private function ensureItemsEditable(Request $request, Invoice $invoice): ?string
    {
        if (in_array($invoice->status, ['draft', 'unpaid'])) {
            return null;
        }

        abort_if($invoice->status !== 'paid', 403, 'Invois tidak boleh diedit.');

        return $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ])['reason'];
    }

    private function notifyItemsEditedAfterPayment(Invoice $invoice, string $reason): void
    {
        TaskNotifier::notifyRole(
            'finance',
            'finance',
            'Item invois dikemaskini selepas bayaran',
            "Invois {$invoice->invoice_number}: item bil diubah selepas bayaran. Sebab: {$reason}",
            route('finance'),
        );
    }

    /**
     * Apply a signed stock change and log it to the same InventoryTransaction ledger
     * Pharmacy dispensing uses, so both sides stay reconcilable. $delta is the intended
     * signed change (negative = deduct); the actual stock level is clamped at 0, matching
     * PharmacyController's autoPopulateBill()/reverseDispenseEffects() behaviour.
     */
    private function adjustStock(InventoryItem $inv, int $delta, string $reference, string $notes): void
    {
        if ($delta === 0) return;

        $newStock = max(0, $inv->stock_quantity + $delta);
        $inv->update(['stock_quantity' => $newStock]);
        $inv->transactions()->create([
            'type'           => $delta < 0 ? 'out' : 'adjustment',
            'quantity_delta' => $delta,
            'quantity_after' => $newStock,
            'reference'      => $reference,
            'notes'          => $notes,
            'performed_by'   => Auth::user()?->name ?? 'System',
        ]);
    }

    /**
     * A prescription-dispensed drug line's stock lives on the rx_number ledger
     * (see PharmacyController::reverseDispenseEffects), so billing-side edits to it
     * must post under that same reference to stay reconcilable. Standalone lines
     * added directly in Billing use the invoice number instead.
     */
    private function stockReferenceFor(InvoiceItem $item, Invoice $invoice): string
    {
        if ($item->prescription_id) {
            $rx = $item->relationLoaded('prescription') ? $item->prescription : Prescription::find($item->prescription_id);
            if ($rx) return $rx->rx_number;
        }

        return $invoice->invoice_number;
    }

    public function storeItem(Request $request, Invoice $invoice)
    {
        $reason = $this->ensureItemsEditable($request, $invoice);

        $data = $request->validate([
            'type'              => ['required', 'in:consultation,procedure,drug,lab,other'],
            'code'              => ['nullable', 'string', 'max:30'],
            'description'       => ['required', 'string', 'max:255'],
            'quantity'          => ['required', 'numeric', 'min:0.01'],
            'unit_price'        => ['required', 'numeric', 'min:0'],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
        ]);

        // Only a 'drug' line can be linked to inventory stock.
        $data['inventory_item_id'] = $data['type'] === 'drug' ? ($data['inventory_item_id'] ?? null) : null;
        $data['total_price'] = round($data['quantity'] * $data['unit_price'], 2);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->items()->create($data);

            if ($data['inventory_item_id']) {
                $inv = InventoryItem::whereKey($data['inventory_item_id'])->lockForUpdate()->first();
                if ($inv) {
                    $this->adjustStock(
                        $inv, -(int) $data['quantity'], $invoice->invoice_number,
                        "Dikeluarkan melalui Bil & Invois · {$invoice->invoice_number}"
                    );
                }
            }

            $invoice->recalc();
        });

        $suffix = $reason ? " · Selepas bayaran · Sebab: {$reason}" : '';
        AuditLog::record(
            'billing.item_add',
            "{$invoice->invoice_number} · {$data['description']}{$suffix}",
            true,
            $reason ? ['reason' => $reason] : []
        );

        if ($reason) $this->notifyItemsEditedAfterPayment($invoice, $reason);

        return back()->with('success', 'Item ditambah.');
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $reason = $this->ensureItemsEditable($request, $invoice);

        $data = $request->validate([
            'quantity'   => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $old = "{$item->quantity} × RM " . number_format($item->unit_price, 2);
        $oldQty = (float) $item->quantity;
        $data['total_price'] = round($data['quantity'] * $data['unit_price'], 2);

        DB::transaction(function () use ($item, $data, $invoice, $oldQty) {
            $item->update($data);

            if ($item->inventory_item_id) {
                $inv = InventoryItem::whereKey($item->inventory_item_id)->lockForUpdate()->first();
                if ($inv) {
                    $qtyDelta = (int) $oldQty - (int) $data['quantity'];
                    $this->adjustStock(
                        $inv, $qtyDelta, $this->stockReferenceFor($item, $invoice),
                        "Pelarasan kuantiti item bil · {$invoice->invoice_number}"
                    );
                }
            }

            $invoice->recalc();
        });

        $new = "{$data['quantity']} × RM " . number_format($data['unit_price'], 2);
        $suffix = $reason ? " · {$old} → {$new} · Selepas bayaran · Sebab: {$reason}" : '';
        AuditLog::record(
            'billing.item_update',
            "{$invoice->invoice_number} · {$item->description}{$suffix}",
            true,
            $reason ? ['old' => $old, 'new' => $new, 'reason' => $reason] : []
        );

        if ($reason) $this->notifyItemsEditedAfterPayment($invoice, $reason);

        return back()->with('success', 'Item dikemaskini.');
    }

    public function destroyItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $reason = $this->ensureItemsEditable($request, $invoice);

        $desc = $item->description;

        DB::transaction(function () use ($item, $invoice) {
            if ($item->inventory_item_id) {
                $inv = InventoryItem::whereKey($item->inventory_item_id)->lockForUpdate()->first();
                if ($inv) {
                    $this->adjustStock(
                        $inv, (int) $item->quantity, $this->stockReferenceFor($item, $invoice),
                        "Item bil dipadam · {$invoice->invoice_number}"
                    );
                }
            }

            $item->delete();
            $invoice->recalc();
        });

        $suffix = $reason ? " · Selepas bayaran · Sebab: {$reason}" : '';
        AuditLog::record(
            'billing.item_remove',
            "{$invoice->invoice_number} · {$desc}{$suffix}",
            true,
            $reason ? ['reason' => $reason] : []
        );

        if ($reason) $this->notifyItemsEditedAfterPayment($invoice, $reason);

        return back()->with('success', 'Item dipadam.');
    }

    public function updateDiscount(Request $request, Invoice $invoice)
    {
        abort_if(! in_array($invoice->status, ['draft', 'unpaid']), 403);
        $data = $request->validate(['discount_amount' => ['required', 'numeric', 'min:0']]);
        $invoice->update(['discount_amount' => $data['discount_amount']]);
        $invoice->recalc();
        return back()->with('success', 'Diskaun dikemaskini.');
    }

    public function finalize(Invoice $invoice)
    {
        abort_if($invoice->status !== 'draft', 403);
        $invoice->update(['status' => 'unpaid']);
        AuditLog::record('billing.finalize', "{$invoice->patient->name} · {$invoice->invoice_number}");

        TaskNotifier::notifyRole(
            'finance',
            'finance',
            'Invois sedia dikutip',
            "Invois {$invoice->invoice_number} untuk {$invoice->patient->name} sedia untuk pembayaran.",
            route('finance'),
        );

        return back()->with('success', 'Invois dimuktamadkan.');
    }

    public function pay(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403);
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,card,duitnow,panel,insurance'],
        ]);

        $invoice->update([
            'status'         => 'paid',
            'payment_method' => $data['payment_method'],
            'paid_at'        => now(),
            'paid_by'        => Auth::user()->name,
        ]);

        AuditLog::record('billing.pay', "{$invoice->patient->name} · {$invoice->invoice_number} · {$data['payment_method']}");

        TaskNotifier::notifyRole(
            'finance',
            'finance',
            'Pembayaran diterima',
            "Invois {$invoice->invoice_number} · RM " . number_format($invoice->total_amount, 2) . ' diterima.',
            route('finance'),
        );

        return back()->with('success', "Pembayaran RM " . number_format($invoice->total_amount, 2) . " diterima.");
    }

    public function updatePaymentMethod(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status !== 'paid', 403);

        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,card,duitnow,panel,insurance'],
            'reason'         => ['required', 'string', 'max:500'],
        ]);

        $old = $invoice->payment_method;
        $invoice->update(['payment_method' => $data['payment_method']]);

        AuditLog::record(
            'billing.payment_method_update',
            "{$invoice->patient->name} · {$invoice->invoice_number} · {$old} → {$data['payment_method']} · Sebab: {$data['reason']}",
            true,
            ['old' => $old, 'new' => $data['payment_method'], 'reason' => $data['reason']]
        );

        TaskNotifier::notifyRole(
            'finance',
            'finance',
            'Kaedah pembayaran dikemaskini',
            "Invois {$invoice->invoice_number}: kaedah bayaran ditukar daripada {$old} kepada {$data['payment_method']}.",
            route('finance'),
        );

        return back()->with('success', 'Kaedah pembayaran dikemaskini.');
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'cancelled', 403);

        $reason = null;
        if ($invoice->status === 'paid') {
            $reason = $request->validate([
                'reason' => ['required', 'string', 'max:500'],
            ])['reason'];
        }

        $invoice->update(['status' => 'cancelled']);

        $suffix = $reason ? " · Selepas bayaran · Sebab: {$reason}" : '';
        AuditLog::record(
            'billing.cancel',
            "{$invoice->patient->name} · {$invoice->invoice_number}{$suffix}",
            true,
            $reason ? ['reason' => $reason] : []
        );

        if ($reason) {
            TaskNotifier::notifyRole(
                'finance',
                'finance',
                'Invois dibatalkan selepas bayaran',
                "Invois {$invoice->invoice_number} ({$invoice->patient->name}) dibatalkan selepas bayaran. Sebab: {$reason}",
                route('finance'),
            );
        }

        return back()->with('success', 'Invois dibatalkan.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Invois yang telah dibayar tidak boleh dipadam.');
        $info = "{$invoice->patient->name} · {$invoice->invoice_number}";
        $invoice->delete();
        AuditLog::record('billing.delete', $info);
        return redirect()->route('billing')->with('success', 'Invois dipadam.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['patient', 'items']);
        AuditLog::record('billing.print', "{$invoice->patient->name} · {$invoice->invoice_number}");
        return view('billing.print', compact('invoice'));
    }
}
