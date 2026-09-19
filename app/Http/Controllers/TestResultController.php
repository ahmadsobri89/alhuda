<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TestResult;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestResultController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'specimen_received_date' => ['required', 'date'],
            'nationality'            => ['nullable', 'string', 'max:255'],
            'category_id'            => ['nullable', 'string', 'max:255'],
            'facility_requestor'     => ['nullable', 'string', 'max:255'],
            'state'                  => ['nullable', 'string', 'max:255'],
            'location_requestor'     => ['nullable', 'string', 'max:500'],
            'requestor_name'         => ['nullable', 'string', 'max:255'],
            'facility_transit'       => ['nullable', 'string', 'max:255'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'results'                => ['required', 'array', 'min:1'],
            'results.*.test_date'    => ['required', 'date'],
            'results.*.test_kit'     => ['required', 'string', 'max:255'],
            'results.*.result'       => ['required', 'string', 'max:255'],
        ]);

        // Simpan baris ujian mengikut turutan yang ditaip, medan bebas teks
        $rows = collect($data['results'])->map(fn ($row) => [
            'test_date' => $row['test_date'],
            'test_kit'  => trim($row['test_kit']),
            'result'    => trim($row['result']),
        ])->values()->all();

        $tr = TestResult::create([
            'patient_id'             => $visit->patient_id,
            'visit_id'               => $visit->id,
            'issued_by'              => Auth::user()?->display_name ?? $visit->doctor_name,
            'issue_date'             => now()->toDateString(),
            'specimen_received_date' => $data['specimen_received_date'],
            'nationality'            => $data['nationality'] ?? null,
            'category_id'            => $data['category_id'] ?? null,
            'facility_requestor'     => $data['facility_requestor'] ?? null,
            'state'                  => $data['state'] ?? null,
            'location_requestor'     => $data['location_requestor'] ?? null,
            'requestor_name'         => $data['requestor_name'] ?? null,
            'facility_transit'       => $data['facility_transit'] ?? null,
            'results'                => $rows,
            'notes'                  => $data['notes'] ?? null,
        ]);

        $summary = $tr->positive_summary ? "positif: {$tr->positive_summary}" : 'semua negatif';
        AuditLog::record('testresult.issue', "{$tr->tr_number} · {$visit->patient->name} · " . count($rows) . " ujian · {$summary}");

        return back()->with('success', "Keputusan ujian {$tr->tr_number} diterbitkan (" . count($rows) . ' ujian).');
    }

    public function destroy(TestResult $testResult)
    {
        $info = "{$testResult->tr_number} · {$testResult->patient->name}";
        $testResult->delete();
        AuditLog::record('testresult.delete', $info);

        return back()->with('success', "Keputusan ujian {$testResult->tr_number} dipadam.");
    }

    public function print(TestResult $testResult)
    {
        $testResult->load(['patient', 'visit']);
        AuditLog::record('testresult.print', "{$testResult->tr_number} · {$testResult->patient->name}");

        return view('testresult.print', ['tr' => $testResult]);
    }

    public function verify(string $token)
    {
        $testResult = TestResult::where('verify_token', $token)
            ->with(['patient'])
            ->firstOrFail();

        return view('testresult.verify', ['tr' => $testResult]);
    }
}
