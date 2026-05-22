<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MedicalCertificate;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MCController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'days'       => ['required', 'integer', 'min:1', 'max:365'],
            'diagnosis'  => ['nullable', 'string', 'max:255'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end   = $start->copy()->addDays($data['days'] - 1);

        $mc = MedicalCertificate::create([
            'patient_id' => $visit->patient_id,
            'visit_id'   => $visit->id,
            'issued_by'  => $visit->doctor_name,
            'issue_date' => now()->toDateString(),
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'days'       => $data['days'],
            'diagnosis'  => $data['diagnosis'] ?? null,
            'notes'      => $data['notes'] ?? null,
        ]);

        AuditLog::record('mc.issue', "{$mc->mc_number} · {$visit->patient->name} · {$data['days']} hari");

        return back()->with('success', "MC {$mc->mc_number} diterbitkan ({$data['days']} hari).");
    }

    public function destroy(MedicalCertificate $mc)
    {
        $info = "{$mc->mc_number} · {$mc->patient->name}";
        $mc->delete();
        AuditLog::record('mc.delete', $info);
        return back()->with('success', "MC {$mc->mc_number} dipadam.");
    }

    public function print(MedicalCertificate $mc)
    {
        $mc->load(['patient', 'visit']);
        AuditLog::record('mc.print', "{$mc->mc_number} · {$mc->patient->name}");
        return view('mc.print', compact('mc'));
    }
}
