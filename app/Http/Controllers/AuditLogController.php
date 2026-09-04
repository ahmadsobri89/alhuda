<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer:id,name')->orderByDesc('created_at');

        if ($request->filled('user')) {
            $s = '%'.$request->user.'%';
            $query->whereHas('causer', fn ($q) => $q->where('name', 'like', $s));
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $logs = $query->paginate($perPage)->onEachSide(0)->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'ts' => $a->created_at->format('d/m/Y H:i:s'),
                'user' => $a->causer?->name ?? 'System',
                'event' => $a->event,
                'log_name' => $a->log_name,
                'subject_id' => $a->subject_id,
                'description' => $a->description,
                'old' => $a->attribute_changes?->get('old', []) ?? [],
                'attributes' => $a->attribute_changes?->get('attributes', []) ?? [],
            ]);

        return Inertia::render('AuditLog', [
            'currentRoute' => 'audit-log',
            'logs' => $logs,
            'logNames' => Activity::select('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'filters' => $request->only(['user', 'event', 'log_name', 'from', 'to', 'per_page']),
        ]);
    }
}
