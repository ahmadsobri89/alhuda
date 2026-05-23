<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\LookupCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    private function formatItem(InventoryItem $item, bool $withTransactions = false): array
    {
        $data = [
            'id'             => $item->id,
            'name'           => $item->name,
            'generic_name'   => $item->generic_name,
            'form'           => $item->form,
            'category'       => $item->category,
            'classification' => $item->classification,
            'lot_number'     => $item->lot_number,
            'expiry_date'    => $item->expiry_date?->format('m/Y'),
            'expiry_raw'     => $item->expiry_date?->format('Y-m-d'),
            'supplier'       => $item->supplier,
            'stock_quantity' => $item->stock_quantity,
            'reorder_level'  => $item->reorder_level,
            'unit_cost'      => (float) $item->unit_cost,
            'selling_price'  => (float) $item->selling_price,
            'unit'           => $item->unit,
            'stock_value'    => $item->stock_value,
            'notes'          => $item->notes,
            'status'         => $item->status,
            'flags'          => $item->flags,
        ];

        if ($withTransactions) {
            $data['transactions'] = $item->transactions->take(20)->map(fn ($t) => [
                'id'             => $t->id,
                'type'           => $t->type,
                'quantity_delta' => $t->quantity_delta,
                'quantity_after' => $t->quantity_after,
                'reference'      => $t->reference,
                'notes'          => $t->notes,
                'performed_by'   => $t->performed_by,
                'created_at'     => $t->created_at->format('d/m/Y H:i'),
            ])->all();
        }

        return $data;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all|low|expiring|poison

        $query = InventoryItem::query()->where('status', '!=', 'discontinued');

        if ($search) {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
            );
        }

        if ($filter === 'low') {
            $query->whereColumn('stock_quantity', '<=', 'reorder_level');
        } elseif ($filter === 'expiring') {
            $query->whereNotNull('expiry_date')
                  ->where('expiry_date', '<=', now()->addDays(90));
        } elseif ($filter === 'poison') {
            $query->whereIn('classification', ['poison_b', 'poison_c', 'controlled']);
        }

        $items = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($item) => $this->formatItem($item));

        // KPIs (always from full dataset, no filter)
        $allActive = InventoryItem::where('status', 'active');
        $kpis = [
            'total_skus'    => (clone $allActive)->count(),
            'low_stock'     => (clone $allActive)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'expiring_90d'  => (clone $allActive)->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(90))->count(),
            'total_value'   => 'RM ' . number_format((clone $allActive)->sum(DB::raw('stock_quantity * unit_cost')), 2),
        ];

        $lookups = LookupCategory::forSlugs(['bentuk_ubat', 'kategori_ubat', 'klasifikasi_ubat']);

        return Inertia::render('Inventory', [
            'currentRoute' => 'inventory',
            'items'        => $items,
            'kpis'         => $kpis,
            'filters'      => ['search' => $search, 'filter' => $filter],
            'lookups'      => $lookups,
        ]);
    }

    public function store(StoreInventoryItemRequest $request)
    {
        DB::transaction(function () use ($request) {
            $item = InventoryItem::create($request->validated());

            if ($item->stock_quantity > 0) {
                $item->transactions()->create([
                    'type'           => 'in',
                    'quantity_delta' => $item->stock_quantity,
                    'quantity_after' => $item->stock_quantity,
                    'reference'      => 'Stok awal',
                    'performed_by'   => Auth::user()?->name ?? 'System',
                ]);
            }

            AuditLog::record('inventory.create', "{$item->name} · Stok: {$item->stock_quantity}");
        });

        return back()->with('success', 'Item inventori berjaya ditambah.');
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem)
    {
        $inventoryItem->update($request->validated());
        AuditLog::record('inventory.update', $inventoryItem->name);

        return back()->with('success', "Rekod {$inventoryItem->name} berjaya dikemaskini.");
    }

    public function adjustStock(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'type'      => ['required', 'in:in,out,adjustment,disposal'],
            'quantity'  => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $inventoryItem) {
            $qty     = (int) $data['quantity'];
            $oldQty  = $inventoryItem->stock_quantity;

            if ($data['type'] === 'in') {
                $delta    = $qty;
                $newStock = $oldQty + $qty;
            } elseif ($data['type'] === 'adjustment') {
                $delta    = $qty - $oldQty;   // user enters the new total
                $newStock = $qty;
            } else {
                // out or disposal
                $delta    = -$qty;
                $newStock = max(0, $oldQty - $qty);
            }

            $inventoryItem->update(['stock_quantity' => $newStock]);

            $inventoryItem->transactions()->create([
                'type'           => $data['type'],
                'quantity_delta' => $delta,
                'quantity_after' => $newStock,
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'performed_by'   => Auth::user()?->name ?? 'Pharmacist',
            ]);

            AuditLog::record(
                "inventory.stock.{$data['type']}",
                "{$inventoryItem->name} · {$oldQty} → {$newStock}"
            );
        });

        return back()->with('success', "Stok {$inventoryItem->name} berjaya dikemaskini.");
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $name = $inventoryItem->name;
        $inventoryItem->update(['status' => 'discontinued']);
        AuditLog::record('inventory.discontinue', $name);

        return back()->with('success', "{$name} ditandakan sebagai dihentikan.");
    }
}
