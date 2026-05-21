<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QueueController extends Controller
{
    public function index()
    {
        $today = now()->format('Y-m-d');
        $now   = now();

        $rows = Appointment::with('patient')
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get()
            ->values()
            ->map(function (Appointment $a, int $i) use ($now) {
                $apptTime  = \Carbon\Carbon::parse($a->appointment_date->format('Y-m-d') . ' ' . $a->appointment_time);
                $waitMins  = max(0, (int) $apptTime->diffInMinutes($now, false));

                return [
                    'id'               => $a->id,
                    'queue_no'         => 'Q-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'patient_id'       => $a->patient_id,
                    'patient_name'     => $a->patient->name,
                    'patient_ic'       => $a->patient->ic_number,
                    'patient_age'      => $a->patient->age_gender,
                    'patient_allergies'=> $a->patient->allergies,
                    'appointment_time' => $a->appointment_time,
                    'type'             => $a->type,
                    'reason'           => $a->reason,
                    'status'           => $a->status,
                    'wait_minutes'     => $waitMins,
                    'notes'            => $a->notes,
                    'doctor_name'      => $a->doctor_name,
                ];
            });

        $stats = [
            'total_today'  => $rows->count(),
            'waiting'      => $rows->whereIn('status', ['confirmed', 'waiting'])->count(),
            'in_room'      => $rows->where('status', 'in_room')->count(),
            'done'         => $rows->where('status', 'done')->count(),
        ];

        return Inertia::render('Queue', [
            'currentRoute' => 'queue',
            'queue'        => $rows->values(),
            'stats'        => $stats,
            'today'        => now()->isoFormat('dddd, D MMMM YYYY'),
        ]);
    }
}
