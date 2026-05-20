<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $patients = Patient::query()
            ->when($search, fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ic_number', 'like', "%{$search}%")
                  ->orWhere('patient_id', 'like', "%{$search}%")
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id'                      => $p->id,
                'patient_id'              => $p->patient_id,
                'name'                    => $p->name,
                'ic_number'               => $p->ic_number,
                'date_of_birth'           => $p->date_of_birth->format('Y-m-d'),
                'age_gender'              => $p->age_gender,
                'gender'                  => $p->gender,
                'phone'                   => $p->phone,
                'email'                   => $p->email,
                'address'                 => $p->address,
                'postcode'                => $p->postcode,
                'city'                    => $p->city,
                'state'                   => $p->state,
                'blood_type'              => $p->blood_type,
                'allergies'               => $p->allergies,
                'conditions'              => $p->conditions ?? [],
                'emergency_contact_name'  => $p->emergency_contact_name,
                'emergency_contact_phone' => $p->emergency_contact_phone,
                'visit_count'             => $p->visit_count,
                'last_visit_at'           => $p->last_visit_at?->diffForHumans(),
                'status'                  => $p->status,
            ]);

        return Inertia::render('Patients', [
            'currentRoute' => 'patients',
            'patients'     => $patients,
            'filters'      => ['search' => $search],
        ]);
    }

    public function store(StorePatientRequest $request)
    {
        $patient = Patient::create($request->validated());
        AuditLog::record('patient.create', "{$patient->patient_id} · {$patient->name}");

        return back()->with('success', "Pesakit {$patient->name} berjaya didaftarkan.");
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());
        AuditLog::record('patient.update', "{$patient->patient_id} · {$patient->name}");

        return back()->with('success', "Rekod {$patient->name} berjaya dikemaskini.");
    }

    public function destroy(Patient $patient)
    {
        $name = $patient->name;
        $pid  = $patient->patient_id;
        $patient->delete();
        AuditLog::record('patient.delete', "{$pid} · {$name}");

        return back()->with('success', "Rekod {$name} berjaya dipadam.");
    }
}
