<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Memo;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemoController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'addressed_to' => ['required', 'string', 'max:255'],
            'subject'      => ['required', 'string', 'max:255'],
            'nature'       => ['required', 'in:normal,urgent,confidential'],
            'content'      => ['required', 'string', 'max:2000'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $memo = Memo::create(array_merge($data, [
            'patient_id' => $visit->patient_id,
            'visit_id'   => $visit->id,
            'issued_by'  => Auth::user()?->display_name ?? $visit->doctor_name,
            'issue_date' => now()->toDateString(),
        ]));

        AuditLog::record('memo.issue', "{$memo->memo_number} · {$visit->patient->name} → {$memo->addressed_to}");

        return back()->with('success', "Memo {$memo->memo_number} diterbitkan.");
    }

    public function destroy(Memo $memo)
    {
        $info = "{$memo->memo_number} · {$memo->patient->name}";
        $memo->delete();
        AuditLog::record('memo.delete', $info);
        return back()->with('success', "Memo {$memo->memo_number} dipadam.");
    }

    public function print(Memo $memo)
    {
        $memo->load(['patient', 'visit']);
        AuditLog::record('memo.print', "{$memo->memo_number} · {$memo->patient->name}");
        return view('memo.print', compact('memo'));
    }

    public function printBlank()
    {
        AuditLog::record('memo.print_blank', 'Templat memo kosong dicetak');
        return view('memo.blank');
    }

    public function verify(string $token)
    {
        $memo = Memo::where('verify_token', $token)
            ->with(['patient'])
            ->firstOrFail();

        return view('memo.verify', compact('memo'));
    }
}
