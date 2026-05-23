<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\QuarantineLetter;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QuarantineController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'quarantine_start' => ['required', 'date'],
            'days'             => ['required', 'integer', 'min:1', 'max:365'],
            'diagnosis'        => ['nullable', 'string', 'max:255'],
            'reason'           => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::parse($data['quarantine_start']);
        $end   = $start->copy()->addDays($data['days'] - 1);

        $qn = QuarantineLetter::create([
            'patient_id'       => $visit->patient_id,
            'visit_id'         => $visit->id,
            'issued_by'        => $visit->doctor_name,
            'issue_date'       => now()->toDateString(),
            'quarantine_start' => $start->toDateString(),
            'quarantine_end'   => $end->toDateString(),
            'days'             => $data['days'],
            'diagnosis'        => $data['diagnosis'] ?? null,
            'reason'           => $data['reason'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);

        AuditLog::record('quarantine.issue', "{$qn->qn_number} · {$visit->patient->name} · {$data['days']} hari");

        return back()->with('success', "Surat Kuarantin {$qn->qn_number} diterbitkan ({$data['days']} hari).");
    }

    public function destroy(QuarantineLetter $quarantine)
    {
        $info = "{$quarantine->qn_number} · {$quarantine->patient->name}";
        $quarantine->delete();
        AuditLog::record('quarantine.delete', $info);
        return back()->with('success', "Surat Kuarantin {$quarantine->qn_number} dipadam.");
    }

    public function print(QuarantineLetter $quarantine)
    {
        $quarantine->load(['patient', 'visit']);
        AuditLog::record('quarantine.print', "{$quarantine->qn_number} · {$quarantine->patient->name}");
        return view('quarantine.print', compact('quarantine'));
    }

    public function verify(string $token)
    {
        $quarantine = QuarantineLetter::where('verify_token', $token)
            ->with(['patient'])
            ->firstOrFail();

        return view('quarantine.verify', compact('quarantine'));
    }
}
