<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\AuditLog;
use App\Models\Appointment;
use App\Models\LookupCategory;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    private const SLOTS = [
        '08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00',
        '14:00','14:30','15:00','15:30','16:00','16:30',
    ];

    private const TYPE_LABELS = [
        'new'            => 'Pesakit Baru',
        'follow_up'      => 'Susulan',
        'annual_checkup' => 'Semakan Tahunan',
        'procedure'      => 'Prosedur',
        'antenatal'      => 'Antenatal',
        'teleconsult'    => 'Teleperubatan',
    ];

    private function formatAppt(Appointment $a): array
    {
        return [
            'id'               => $a->id,
            'patient_id'       => $a->patient_id,
            'patient_name'     => $a->patient->name,
            'patient_ic'       => $a->patient->ic_number,
            'patient_allergies'=> $a->patient->allergies,
            'doctor_name'      => $a->doctor_name,
            'appointment_date' => $a->appointment_date->format('Y-m-d'),
            'appointment_time' => $a->appointment_time,
            'duration_minutes' => $a->duration_minutes,
            'type'             => $a->type,
            'type_label'       => self::TYPE_LABELS[$a->type] ?? $a->type,
            'reason'           => $a->reason,
            'status'           => $a->status,
            'notes'            => $a->notes,
            'visit_id'         => $a->visit?->id,
        ];
    }

    public function index(Request $request)
    {
        // Week start (Monday)
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->addDays(6); // Mon–Sun

        // Fetch week's appointments
        $weekAppts = Appointment::with('patient', 'visit')
            ->whereBetween('appointment_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
            ->orderBy('appointment_time')
            ->get();

        // Build week slot map: { "2026-05-20": { "09:00": appt, ... }, ... }
        $weekMap = [];
        foreach ($weekAppts as $appt) {
            $dateKey = $appt->appointment_date->format('Y-m-d');
            $timeKey = $appt->appointment_time;
            if (! isset($weekMap[$dateKey])) $weekMap[$dateKey] = [];
            $weekMap[$dateKey][$timeKey] = $this->formatAppt($appt);
        }

        // Build week dates array
        $weekDates = [];
        for ($i = 0; $i <= 6; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $weekDates[] = [
                'date'     => $d->format('Y-m-d'),
                'label'    => $d->translatedFormat('D d'),
                'is_today' => $d->isToday(),
                'count'    => isset($weekMap[$d->format('Y-m-d')]) ? count($weekMap[$d->format('Y-m-d')]) : 0,
            ];
        }

        // Today's ordered list
        $todayKey  = now()->format('Y-m-d');
        $todayList = $weekAppts
            ->filter(fn ($a) => $a->appointment_date->format('Y-m-d') === $todayKey)
            ->sortBy('appointment_time')
            ->map(fn ($a) => $this->formatAppt($a))
            ->values();

        // Stats for the week
        $stats = [
            'total'     => $weekAppts->count(),
            'confirmed' => $weekAppts->where('status', 'confirmed')->count(),
            'done'      => $weekAppts->where('status', 'done')->count(),
            'cancelled' => $weekAppts->where('status', 'cancelled')->count(),
        ];

        $patients = Patient::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'ic_number', 'patient_id', 'allergies', 'conditions']);

        $lookups = LookupCategory::forSlugs(['jenis_temujanji', 'status_temujanji', 'tempoh_temujanji']);

        return Inertia::render('Appointments', [
            'currentRoute' => 'appointments',
            'weekStart'    => $weekStart->format('Y-m-d'),
            'weekEnd'      => $weekEnd->format('Y-m-d'),
            'weekDates'    => $weekDates,
            'weekMap'      => $weekMap,
            'todayList'    => $todayList,
            'slots'        => self::SLOTS,
            'stats'        => $stats,
            'patients'     => $patients,
            'today'        => now()->format('Y-m-d'),
            'lookups'      => $lookups,
        ]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $appt = Appointment::create(array_merge(
            $request->validated(),
            ['user_id' => Auth::id()]
        ));

        AuditLog::record(
            'appointment.create',
            "{$appt->patient->name} · {$appt->appointment_date->format('d/m/Y')} {$appt->appointment_time}"
        );

        return back()->with('success', "Temujanji berjaya ditetapkan untuk {$appt->patient->name}.");
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        AuditLog::record(
            'appointment.update',
            "{$appointment->patient->name} · {$appointment->appointment_date->format('d/m/Y')} {$appointment->appointment_time}"
        );

        return back()->with('success', 'Temujanji berjaya dikemaskini.');
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => ['required', 'in:confirmed,waiting,in_room,done,cancelled,no_show']]);

        $appointment->update(['status' => $request->status]);

        AuditLog::record(
            "appointment.{$request->status}",
            "{$appointment->patient->name} · {$appointment->appointment_time}"
        );

        return back()->with('success', 'Status temujanji dikemaskini.');
    }

    public function startEmr(Appointment $appointment)
    {
        // Reuse the existing EMR for this appointment, or open a new one.
        $visit = Visit::firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id'      => $appointment->patient_id,
                'user_id'         => Auth::id(),
                'doctor_name'     => $appointment->doctor_name,
                'visit_date'      => $appointment->appointment_date->format('Y-m-d'),
                'chief_complaint' => $appointment->reason,
                'status'          => 'open',
            ]
        );

        if ($visit->wasRecentlyCreated) {
            AuditLog::record(
                'emr.create',
                "{$appointment->patient->name} · {$appointment->appointment_date->format('d/m/Y')} (dari temujanji)"
            );

            // Mark the appointment as in-consultation when a record is first opened.
            if (! in_array($appointment->status, ['done', 'cancelled', 'no_show'])) {
                $appointment->update(['status' => 'in_room']);
            }
        }

        return redirect()->route('emr', ['visit' => $visit->id]);
    }

    public function destroy(Appointment $appointment)
    {
        $info = "{$appointment->patient->name} · {$appointment->appointment_date->format('d/m/Y')} {$appointment->appointment_time}";
        $appointment->delete();

        AuditLog::record('appointment.delete', $info);

        return back()->with('success', 'Temujanji berjaya dipadam.');
    }
}
