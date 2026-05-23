<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\LookupCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\QuarantineLetter;
use App\Models\Visit;
use App\Models\VisitDiagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EMRController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::with('patient')
            ->withCount('diagnoses')
            ->orderByDesc('visit_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->whereHas('patient', fn ($q) =>
                $q->where('name', 'like', $s)->orWhere('ic_number', 'like', $s)
            );
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $visits = $query->paginate(30)->withQueryString()
            ->through(fn ($v) => [
                'id'              => $v->id,
                'patient_name'    => $v->patient->name,
                'patient_ic'      => $v->patient->ic_number,
                'doctor_name'     => $v->doctor_name,
                'visit_date'      => $v->visit_date->format('Y-m-d'),
                'chief_complaint' => $v->chief_complaint,
                'status'          => $v->status,
                'diagnoses_count' => $v->diagnoses_count,
            ]);

        $selected = null;
        if ($request->filled('visit')) {
            $v = Visit::with(['patient', 'vitals', 'diagnoses', 'medicalCertificates', 'referrals', 'timeSlips', 'quarantineLetters', 'prescriptions.items'])->find($request->visit);
            if ($v) $selected = $this->formatVisit($v);
        }

        $patients = Patient::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'ic_number', 'patient_id', 'date_of_birth', 'gender', 'blood_type', 'allergies', 'conditions']);

        $lookups = LookupCategory::forSlugs(['keutamaan_rujukan', 'kekerapan_dos', 'arahan_dos', 'bentuk_ubat']);

        $drugItems = InventoryItem::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'form', 'unit', 'selling_price', 'stock_quantity']);

        return Inertia::render('EMR', [
            'currentRoute' => 'emr',
            'visits'       => $visits,
            'selected'     => $selected,
            'patients'     => $patients,
            'filters'      => $request->only(['search', 'status', 'visit']),
            'today'        => now()->format('Y-m-d'),
            'lookups'      => $lookups,
            'drugItems'    => $drugItems,
        ]);
    }

    private function formatVisit(Visit $v): array
    {
        $p = $v->patient;
        $dob = $p->date_of_birth;
        $age = $dob ? $dob->age : null;

        return [
            'id'                 => $v->id,
            'patient_id'         => $v->patient_id,
            'patient_name'       => $p->name,
            'patient_ic'         => $p->ic_number,
            'patient_id_str'     => $p->patient_id,
            'patient_allergies'  => $p->allergies,
            'patient_blood_type' => $p->blood_type,
            'patient_conditions' => $p->conditions ?? [],
            'patient_age_gender' => $age ? ($age . ($p->gender === 'male' ? 'L' : 'P')) : null,
            'doctor_name'        => $v->doctor_name,
            'visit_date'         => $v->visit_date->format('Y-m-d'),
            'chief_complaint'    => $v->chief_complaint,
            'status'             => $v->status,
            'soap_s'             => $v->soap_s,
            'soap_o'             => $v->soap_o,
            'soap_a'             => $v->soap_a,
            'soap_p'             => $v->soap_p,
            'signed_at'          => $v->signed_at?->format('d/m/Y H:i'),
            'signed_by'          => $v->signed_by,
            'vitals'             => $v->vitals ? [
                'bp_systolic'  => $v->vitals->bp_systolic,
                'bp_diastolic' => $v->vitals->bp_diastolic,
                'heart_rate'   => $v->vitals->heart_rate,
                'temperature'  => $v->vitals->temperature,
                'spo2'         => $v->vitals->spo2,
                'weight'       => $v->vitals->weight,
                'height'       => $v->vitals->height,
            ] : null,
            'diagnoses' => $v->diagnoses->map(fn ($d) => [
                'id'          => $d->id,
                'icd_code'    => $d->icd_code,
                'description' => $d->description,
                'type'        => $d->type,
            ])->values()->toArray(),
            'mcs' => $v->medicalCertificates->map(fn ($mc) => [
                'id'         => $mc->id,
                'mc_number'  => $mc->mc_number,
                'start_date' => $mc->start_date->format('d/m/Y'),
                'end_date'   => $mc->end_date->format('d/m/Y'),
                'days'       => $mc->days,
                'diagnosis'  => $mc->diagnosis,
                'issued_by'  => $mc->issued_by,
                'issue_date' => $mc->issue_date->format('d/m/Y'),
            ])->values()->toArray(),
            'referrals' => $v->referrals->map(fn ($r) => [
                'id'               => $r->id,
                'ref_number'       => $r->ref_number,
                'referred_to'      => $r->referred_to,
                'referred_to_dept' => $r->referred_to_dept,
                'urgency'          => $r->urgency,
                'reason'           => $r->reason,
                'issued_by'        => $r->issued_by,
                'issue_date'       => $r->issue_date->format('d/m/Y'),
            ])->values()->toArray(),
            'time_slips' => $v->timeSlips->map(fn ($ts) => [
                'id'             => $ts->id,
                'slip_number'    => $ts->slip_number,
                'slip_date'      => $ts->slip_date->format('d/m/Y'),
                'arrival_time'   => substr($ts->arrival_time, 0, 5),
                'departure_time' => substr($ts->departure_time, 0, 5),
                'purpose'        => $ts->purpose,
                'issued_by'      => $ts->issued_by,
            ])->values()->toArray(),
            'quarantines' => $v->quarantineLetters->map(fn ($qn) => [
                'id'               => $qn->id,
                'qn_number'        => $qn->qn_number,
                'quarantine_start' => $qn->quarantine_start->format('d/m/Y'),
                'quarantine_end'   => $qn->quarantine_end->format('d/m/Y'),
                'days'             => $qn->days,
                'diagnosis'        => $qn->diagnosis,
                'reason'           => $qn->reason,
                'issued_by'        => $qn->issued_by,
                'issue_date'       => $qn->issue_date->format('d/m/Y'),
            ])->values()->toArray(),
            'prescriptions' => $v->prescriptions->map(fn ($rx) => [
                'id'         => $rx->id,
                'rx_number'  => $rx->rx_number,
                'status'     => $rx->status,
                'created_at' => $rx->created_at->format('d/m/Y H:i'),
                'items'      => $rx->items->map(fn ($i) => [
                    'drug_name' => $i->drug_name,
                    'dosage'    => $i->dosage,
                    'frequency' => $i->frequency,
                    'quantity'  => $i->quantity,
                    'drug_unit' => $i->drug_unit,
                ])->values()->toArray(),
            ])->values()->toArray(),
        ];
    }

    public function storePrescription(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'notes'               => ['nullable', 'string', 'max:1000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.drug_name'   => ['required', 'string', 'max:255'],
            'items.*.drug_unit'   => ['nullable', 'string', 'max:100'],
            'items.*.dosage'      => ['nullable', 'string', 'max:100'],
            'items.*.frequency'   => ['nullable', 'string', 'max:100'],
            'items.*.duration'    => ['nullable', 'string', 'max:100'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'items.*.instructions'=> ['nullable', 'string', 'max:255'],
        ]);

        $rx = Prescription::create([
            'patient_id'         => $visit->patient_id,
            'visit_id'           => $visit->id,
            'prescribing_doctor' => $visit->doctor_name,
            'user_id'            => Auth::id(),
            'status'             => 'pending',
            'notes'              => $data['notes'] ?? null,
            'drug_check_passed'  => true,
            'drug_check_notes'   => 'Dijana daripada EMR.',
        ]);

        foreach ($data['items'] as $item) {
            $rx->items()->create($item);
        }

        AuditLog::record('emr.prescription', "{$visit->patient->name} · {$rx->rx_number} · " . count($data['items']) . ' ubat');

        return back()->with('success', "Preskripsi {$rx->rx_number} dihantar ke farmasi.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'      => ['required', 'exists:patients,id'],
            'doctor_name'     => ['required', 'string', 'max:255'],
            'visit_date'      => ['required', 'date'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
        ]);

        $visit = Visit::create(array_merge($data, [
            'user_id' => Auth::id(),
            'status'  => 'open',
        ]));

        AuditLog::record('emr.create', "{$visit->patient->name} · {$visit->visit_date->format('d/m/Y')}");

        return redirect()->route('emr', ['visit' => $visit->id])
            ->with('success', "Rekod baru dibuka untuk {$visit->patient->name}.");
    }

    public function updateSoap(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'soap_s' => ['nullable', 'string'],
            'soap_o' => ['nullable', 'string'],
            'soap_a' => ['nullable', 'string'],
            'soap_p' => ['nullable', 'string'],
        ]);

        $visit->update($data);

        AuditLog::record('emr.soap', "{$visit->patient->name} · {$visit->visit_date->format('d/m/Y')}");

        return back()->with('success', 'Nota SOAP disimpan.');
    }

    public function storeVitals(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'bp_systolic'  => ['nullable', 'integer', 'between:60,250'],
            'bp_diastolic' => ['nullable', 'integer', 'between:40,150'],
            'heart_rate'   => ['nullable', 'integer', 'between:30,250'],
            'temperature'  => ['nullable', 'numeric', 'between:34,42'],
            'spo2'         => ['nullable', 'integer', 'between:70,100'],
            'weight'       => ['nullable', 'numeric', 'between:1,300'],
            'height'       => ['nullable', 'integer', 'between:30,250'],
        ]);

        $filtered = array_filter($data, fn ($v) => $v !== null);
        $visit->vitals()->updateOrCreate(['visit_id' => $visit->id], $filtered);

        AuditLog::record('emr.vitals', "{$visit->patient->name}");

        return back()->with('success', 'Tanda vital disimpan.');
    }

    public function storeDiagnosis(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'icd_code'    => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:primary,secondary'],
        ]);

        $visit->diagnoses()->create($data);

        AuditLog::record('emr.diagnosis', "{$visit->patient->name} · {$data['icd_code']}");

        return back()->with('success', 'Diagnosis ditambah.');
    }

    public function destroyDiagnosis(Visit $visit, VisitDiagnosis $diagnosis)
    {
        $diagnosis->delete();
        AuditLog::record('emr.diagnosis_delete', "{$visit->patient->name}");
        return back()->with('success', 'Diagnosis dipadam.');
    }

    public function close(Visit $visit)
    {
        $visit->update([
            'status'    => 'closed',
            'signed_at' => now(),
            'signed_by' => Auth::user()->name,
        ]);

        AuditLog::record('emr.close', "{$visit->patient->name} · {$visit->visit_date->format('d/m/Y')}");

        return back()->with('success', 'Rekod ditandatangan dan ditutup.');
    }

    public function destroy(Visit $visit)
    {
        $info = "{$visit->patient->name} · {$visit->visit_date->format('d/m/Y')}";
        $visit->delete();
        AuditLog::record('emr.delete', $info);
        return redirect()->route('emr')->with('success', 'Rekod perubatan dipadam.');
    }
}
