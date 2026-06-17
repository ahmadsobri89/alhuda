<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TimeSlip;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeSlipController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'arrival_time'   => ['required', 'date_format:H:i'],
            'departure_time' => ['required', 'date_format:H:i', 'after:arrival_time'],
            'purpose'        => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $slip = TimeSlip::create(array_merge($data, [
            'patient_id' => $visit->patient_id,
            'visit_id'   => $visit->id,
            'issued_by'  => Auth::user()?->display_name ?? $visit->doctor_name,
            'slip_date'  => now()->toDateString(),
        ]));

        AuditLog::record('timeslip.issue', "{$slip->slip_number} · {$visit->patient->name}");

        return back()->with('success', "Slip Masa {$slip->slip_number} diterbitkan.");
    }

    public function destroy(TimeSlip $timeslip)
    {
        $info = "{$timeslip->slip_number} · {$timeslip->patient->name}";
        $timeslip->delete();
        AuditLog::record('timeslip.delete', $info);
        return back()->with('success', "Slip Masa {$timeslip->slip_number} dipadam.");
    }

    public function print(TimeSlip $timeslip)
    {
        $timeslip->load(['patient', 'visit']);
        AuditLog::record('timeslip.print', "{$timeslip->slip_number} · {$timeslip->patient->name}");
        return view('timeslip.print', compact('timeslip'));
    }

    public function verify(string $token)
    {
        $timeslip = TimeSlip::where('verify_token', $token)
            ->with(['patient'])
            ->firstOrFail();

        return view('timeslip.verify', compact('timeslip'));
    }
}
