<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\AuditLog;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServicesController extends Controller
{
    private function formatService(Service $service): array
    {
        return [
            'id'       => $service->id,
            'code'     => $service->code,
            'name'     => $service->name,
            'category' => $service->category,
            'price'    => (float) $service->price,
            'notes'    => $service->notes,
            'status'   => $service->status,
        ];
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = Service::query();

        if ($search) {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
            );
        }

        $services = $query->orderBy('name')
            ->paginate($perPage)
            ->onEachSide(0)
            ->withQueryString()
            ->through(fn ($service) => $this->formatService($service));

        $kpis = [
            'total_services' => Service::where('status', 'active')->count(),
        ];

        return Inertia::render('Services', [
            'currentRoute' => 'services',
            'services'     => $services,
            'kpis'         => $kpis,
            'filters'      => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated() + ['status' => 'active']);
        AuditLog::record('services.create', "{$service->name} · RM " . number_format($service->price, 2));

        return back()->with('success', 'Perkhidmatan berjaya ditambah.');
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated());
        AuditLog::record('services.update', $service->name);

        return back()->with('success', "Rekod {$service->name} berjaya dikemaskini.");
    }

    public function destroy(Service $service)
    {
        $name = $service->name;
        $service->update(['status' => 'discontinued']);
        AuditLog::record('services.discontinue', $name);

        return back()->with('success', "{$name} ditandakan sebagai dihentikan.");
    }
}
