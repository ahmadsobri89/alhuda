<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ReferralLetter;
use App\Models\Visit;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'referred_to'      => ['required', 'string', 'max:255'],
            'referred_to_dept' => ['nullable', 'string', 'max:255'],
            'urgency'          => ['required', 'in:routine,urgent,emergency'],
            'reason'           => ['required', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string', 'max:2000'],
            'relevant_history' => ['nullable', 'string', 'max:2000'],
        ]);

        $ref = ReferralLetter::create(array_merge($data, [
            'patient_id' => $visit->patient_id,
            'visit_id'   => $visit->id,
            'issued_by'  => $visit->doctor_name,
            'issue_date' => now()->toDateString(),
        ]));

        AuditLog::record('referral.issue', "{$ref->ref_number} · {$visit->patient->name} → {$ref->referred_to}");

        return back()->with('success', "Surat Rujukan {$ref->ref_number} diterbitkan.");
    }

    public function destroy(ReferralLetter $referral)
    {
        $info = "{$referral->ref_number} · {$referral->patient->name}";
        $referral->delete();
        AuditLog::record('referral.delete', $info);
        return back()->with('success', "Rujukan {$referral->ref_number} dipadam.");
    }

    public function print(ReferralLetter $referral)
    {
        $referral->load(['patient', 'visit']);
        AuditLog::record('referral.print', "{$referral->ref_number} · {$referral->patient->name}");
        return view('referral.print', compact('referral'));
    }
}
