<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('patient')
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

        $invoices = $query->paginate(30)->withQueryString()
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

        return Inertia::render('Billing', [
            'currentRoute' => 'billing',
            'invoices'     => $invoices,
            'selected'     => $selected,
            'patients'     => $patients,
            'stats'        => $stats,
            'filters'      => $request->only(['search', 'status', 'invoice']),
            'today'        => $today,
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

    public function storeItem(Request $request, Invoice $invoice)
    {
        abort_if(! in_array($invoice->status, ['draft', 'unpaid']), 403, 'Invois tidak boleh diedit.');

        $data = $request->validate([
            'type'        => ['required', 'in:consultation,procedure,drug,lab,other'],
            'code'        => ['nullable', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $data['total_price'] = round($data['quantity'] * $data['unit_price'], 2);
        $invoice->items()->create($data);
        $invoice->recalc();

        AuditLog::record('billing.item_add', "{$invoice->invoice_number} · {$data['description']}");

        return back()->with('success', 'Item ditambah.');
    }

    public function destroyItem(Invoice $invoice, InvoiceItem $item)
    {
        abort_if(! in_array($invoice->status, ['draft', 'unpaid']), 403);
        $item->delete();
        $invoice->recalc();
        AuditLog::record('billing.item_remove', $invoice->invoice_number);
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

        return back()->with('success', "Pembayaran RM " . number_format($invoice->total_amount, 2) . " diterima.");
    }

    public function cancel(Invoice $invoice)
    {
        abort_if($invoice->status === 'cancelled', 403);
        $invoice->update(['status' => 'cancelled']);
        AuditLog::record('billing.cancel', "{$invoice->patient->name} · {$invoice->invoice_number}");
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
}
