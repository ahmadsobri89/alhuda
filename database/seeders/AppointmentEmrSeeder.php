<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Dummy data for manually testing "Masukkan EMR dari temujanji".
 *
 * Seeds three of today's appointments:
 *   1. confirmed, no EMR   → drawer shows "Masukkan EMR" (creates a new record)
 *   2. waiting,   no EMR   → start EMR straight from today's queue
 *   3. confirmed, has EMR  → drawer shows "Buka EMR" (re-opens the existing record)
 *
 * Idempotent: re-running updates the same rows instead of duplicating them.
 */
class AppointmentEmrSeeder extends Seeder
{
    public function run(): void
    {
        $doc    = 'Dr. Aiman Rashid';
        $userId = User::where('email', 'aiman@alhuda.my')->value('id') ?? User::query()->value('id');
        $today  = Carbon::now()->format('Y-m-d');

        // ── 1. Confirmed appointment, no EMR yet ───────────────────────────
        $pBaru = Patient::updateOrCreate(
            ['ic_number' => '900112-14-0001'],
            [
                'name' => 'Iskandar bin Daud', 'date_of_birth' => '1990-01-12', 'gender' => 'male',
                'phone' => '012-7001001', 'address' => 'No. 1, Jalan Ujian', 'postcode' => '43000',
                'city' => 'Kajang', 'state' => 'Selangor', 'blood_type' => 'O+', 'conditions' => [],
                'status' => 'active',
            ]
        );
        Appointment::updateOrCreate(
            ['patient_id' => $pBaru->id, 'appointment_date' => $today, 'appointment_time' => '11:30'],
            [
                'doctor_name' => $doc, 'duration_minutes' => 30, 'type' => 'new',
                'reason' => 'Demam dan sakit tekak sejak 2 hari', 'status' => 'confirmed',
            ]
        );

        // ── 2. Waiting appointment, no EMR yet ─────────────────────────────
        $pMenunggu = Patient::updateOrCreate(
            ['ic_number' => '850623-08-0002'],
            [
                'name' => 'Rohaya binti Mahmud', 'date_of_birth' => '1985-06-23', 'gender' => 'female',
                'phone' => '012-7002002', 'address' => 'No. 2, Jalan Ujian', 'postcode' => '43000',
                'city' => 'Kajang', 'state' => 'Selangor', 'blood_type' => 'A+', 'allergies' => 'Penicillin',
                'conditions' => ['HTN'], 'status' => 'active',
            ]
        );
        Appointment::updateOrCreate(
            ['patient_id' => $pMenunggu->id, 'appointment_date' => $today, 'appointment_time' => '14:00'],
            [
                'doctor_name' => $doc, 'duration_minutes' => 30, 'type' => 'follow_up',
                'reason' => 'Kawalan tekanan darah bulanan', 'status' => 'waiting',
            ]
        );

        // ── 3. Confirmed appointment with an EMR already opened ────────────
        $pAdaEmr = Patient::updateOrCreate(
            ['ic_number' => '720418-10-0003'],
            [
                'name' => 'Chong Wei Leong', 'date_of_birth' => '1972-04-18', 'gender' => 'male',
                'phone' => '012-7003003', 'address' => 'No. 3, Jalan Ujian', 'postcode' => '43000',
                'city' => 'Kajang', 'state' => 'Selangor', 'blood_type' => 'B+',
                'conditions' => ['T2DM'], 'status' => 'active',
            ]
        );
        $apptAdaEmr = Appointment::updateOrCreate(
            ['patient_id' => $pAdaEmr->id, 'appointment_date' => $today, 'appointment_time' => '14:30'],
            [
                'doctor_name' => $doc, 'duration_minutes' => 30, 'type' => 'follow_up',
                'reason' => 'Semakan ubat kencing manis', 'status' => 'in_room',
            ]
        );
        Visit::firstOrCreate(
            ['appointment_id' => $apptAdaEmr->id],
            [
                'patient_id'      => $pAdaEmr->id, 'user_id' => $userId, 'doctor_name' => $doc,
                'visit_date'      => $today, 'chief_complaint' => $apptAdaEmr->reason,
                'status'          => 'open',
            ]
        );
    }
}
