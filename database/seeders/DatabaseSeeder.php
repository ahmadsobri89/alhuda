<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SecurityPolicy;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitVital;
use App\Models\VisitDiagnosis;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clinic staff
        $staff = [
            ['name' => 'Dr. Aiman Rashid', 'role' => 'doctor',       'mmc_number' => 'MMC-87231', 'email' => 'aiman@alhuda.my',   'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Nurse Fatimah',     'role' => 'nurse',         'mmc_number' => null,        'email' => 'fatimah@alhuda.my', 'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Encik Zahid',       'role' => 'admin',         'mmc_number' => null,        'email' => 'zahid@alhuda.my',   'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Pn. Salina',        'role' => 'receptionist',  'mmc_number' => null,        'email' => 'salina@alhuda.my',  'mfa_enabled' => false, 'status' => 'active'],
            ['name' => 'Mr. Vinod',         'role' => 'pharmacist',    'mmc_number' => null,        'email' => 'vinod@alhuda.my',   'mfa_enabled' => true,  'status' => 'inactive'],
        ];

        foreach ($staff as $s) {
            User::updateOrCreate(['email' => $s['email']], array_merge($s, ['password' => Hash::make('password'), 'email_verified_at' => now()]));
        }

        User::updateOrCreate(['email' => 'test@alhuda.my'], [
            'name' => 'Dr. Aiman Rashid', 'role' => 'doctor', 'mmc_number' => 'MMC-87231',
            'mfa_enabled' => true, 'status' => 'active',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);

        // Security policies
        $policies = [
            ['key' => 'min_password_length',     'label' => 'Minimum 12 characters',              'enabled' => true],
            ['key' => 'password_complexity',     'label' => 'Require uppercase + number + symbol', 'enabled' => true],
            ['key' => 'password_expiry',         'label' => 'Force change every 90 days',          'enabled' => true],
            ['key' => 'mfa_admin',               'label' => 'Require MFA for all admins',          'enabled' => true],
            ['key' => 'mfa_doctor',              'label' => 'Require MFA for doctors',             'enabled' => true],
            ['key' => 'ip_whitelist_superadmin', 'label' => 'IP whitelist for super-admin',        'enabled' => false],
            ['key' => 'session_timeout',         'label' => 'Auto logout after 15 minutes idle',   'enabled' => true],
            ['key' => 'audit_log',               'label' => 'Enable full audit trail',             'enabled' => true],
        ];
        foreach ($policies as $p) {
            SecurityPolicy::updateOrCreate(['key' => $p['key']], $p);
        }

        // Sample patients
        $patients = [
            [
                'name' => 'Aminah binti Hassan', 'ic_number' => '780229-08-5234',
                'date_of_birth' => '1978-02-29', 'gender' => 'female',
                'phone' => '012-3456789', 'email' => 'aminah@gmail.com',
                'address' => 'No. 12, Jalan Mawar', 'postcode' => '43000', 'city' => 'Kajang', 'state' => 'Selangor',
                'blood_type' => 'A+', 'allergies' => 'Penicillin',
                'conditions' => ['HTN', 'T2DM'],
                'emergency_contact_name' => 'Hassan bin Ali', 'emergency_contact_phone' => '013-9876543',
                'visit_count' => 12, 'last_visit_at' => now(),
            ],
            [
                'name' => 'Lim Kok Wing', 'ic_number' => '650412-14-8821',
                'date_of_birth' => '1965-04-12', 'gender' => 'male',
                'phone' => '011-2233445', 'email' => null,
                'address' => 'No. 5, Taman Sejahtera', 'postcode' => '68000', 'city' => 'Ampang', 'state' => 'Selangor',
                'blood_type' => 'O+', 'allergies' => null,
                'conditions' => ['CAD', 'Dyslipidemia'],
                'emergency_contact_name' => 'Lim Mei Lin', 'emergency_contact_phone' => '012-5566778',
                'visit_count' => 24, 'last_visit_at' => now()->subHours(2),
            ],
            [
                'name' => 'Tan Wei Ming', 'ic_number' => '920815-10-7733',
                'date_of_birth' => '1992-08-15', 'gender' => 'male',
                'phone' => '019-8877665', 'email' => 'tanwm@yahoo.com',
                'address' => 'Unit 3-12, Residensi Harmoni', 'postcode' => '56000', 'city' => 'Cheras', 'state' => 'Kuala Lumpur',
                'blood_type' => 'B+', 'allergies' => 'Aspirin',
                'conditions' => ['Asthma'],
                'emergency_contact_name' => 'Tan Ah Kow', 'emergency_contact_phone' => '011-3344556',
                'visit_count' => 7, 'last_visit_at' => now()->subHours(3),
            ],
            [
                'name' => 'Siti Nor Aisyah', 'ic_number' => '850101-14-5678',
                'date_of_birth' => '1985-01-01', 'gender' => 'female',
                'phone' => '017-6655443', 'email' => 'siti.aisyah@hotmail.com',
                'address' => 'No. 88, Jalan Cempaka', 'postcode' => '43300', 'city' => 'Semenyih', 'state' => 'Selangor',
                'blood_type' => 'AB+', 'allergies' => null,
                'conditions' => [],
                'emergency_contact_name' => 'Mohd Farid', 'emergency_contact_phone' => '014-7788990',
                'visit_count' => 3, 'last_visit_at' => now()->subDay(),
            ],
            [
                'name' => 'Rajesh Kumar', 'ic_number' => '010322-08-1145',
                'date_of_birth' => '2001-03-22', 'gender' => 'male',
                'phone' => '016-3344221', 'email' => null,
                'address' => 'No. 22, Jalan Utama', 'postcode' => '47500', 'city' => 'Subang Jaya', 'state' => 'Selangor',
                'blood_type' => 'O-', 'allergies' => 'Sulfa',
                'conditions' => [],
                'emergency_contact_name' => 'Kumar Pillai', 'emergency_contact_phone' => '012-8899001',
                'visit_count' => 2, 'last_visit_at' => now()->subDay(),
            ],
            [
                'name' => 'Nurul Ain Zainal', 'ic_number' => '950707-03-9988',
                'date_of_birth' => '1995-07-07', 'gender' => 'female',
                'phone' => '013-2233998', 'email' => 'nurulain@gmail.com',
                'address' => 'No. 4, Persiaran Damai', 'postcode' => '43650', 'city' => 'Bangi', 'state' => 'Selangor',
                'blood_type' => 'A-', 'allergies' => null,
                'conditions' => ['Pregnancy W24'],
                'emergency_contact_name' => 'Zainal Abidin', 'emergency_contact_phone' => '019-5544332',
                'visit_count' => 8, 'last_visit_at' => now()->subDays(2),
            ],
            [
                'name' => 'Hassan bin Ali', 'ic_number' => '750314-08-1122',
                'date_of_birth' => '1975-03-14', 'gender' => 'male',
                'phone' => '012-9988776', 'email' => null,
                'address' => 'No. 77, Taman Maju', 'postcode' => '43000', 'city' => 'Kajang', 'state' => 'Selangor',
                'blood_type' => 'B-', 'allergies' => null,
                'conditions' => ['HTN'],
                'emergency_contact_name' => 'Fatimah binti Yusof', 'emergency_contact_phone' => '011-6677889',
                'visit_count' => 18, 'last_visit_at' => now()->subDays(8),
            ],
            [
                'name' => 'Wong Mei Ling', 'ic_number' => '880905-10-4477',
                'date_of_birth' => '1988-09-05', 'gender' => 'female',
                'phone' => '018-7766554', 'email' => 'meilingwong@gmail.com',
                'address' => 'No. 33, Jalan Puteri', 'postcode' => '47810', 'city' => 'Petaling Jaya', 'state' => 'Selangor',
                'blood_type' => 'AB-', 'allergies' => null,
                'conditions' => ['Hypothyroid'],
                'emergency_contact_name' => 'Wong Ah Beng', 'emergency_contact_phone' => '013-1122334',
                'visit_count' => 5, 'last_visit_at' => now()->subDays(10),
            ],
        ];

        foreach ($patients as $p) {
            Patient::updateOrCreate(['ic_number' => $p['ic_number']], $p);
        }

        // Sample prescriptions
        if (Prescription::count() === 0) {
            $p1 = Patient::where('ic_number', '780229-08-5234')->first();
            $p2 = Patient::where('ic_number', '650412-14-8821')->first();
            $p3 = Patient::where('ic_number', '920815-10-7733')->first();
            $p4 = Patient::where('ic_number', '010322-08-1145')->first();
            $p5 = Patient::where('ic_number', '880905-10-4477')->first();

            $rxData = [
                [
                    'patient' => $p1, 'doctor' => 'Dr. Aiman Rashid', 'status' => 'pending',
                    'notes' => 'Pesakit alah Penicillin. Elak antibiotik beta-lactam.',
                    'drug_check_notes' => 'Tiada interaksi kritikal. Alahan Penicillin diambil kira.',
                    'items' => [
                        ['drug_name'=>'Metformin 500mg', 'dosage'=>'500mg', 'frequency'=>'BD - 2x sehari', 'duration'=>'30 hari', 'quantity'=>60, 'instructions'=>'Selepas makan'],
                        ['drug_name'=>'Amlodipine 5mg',  'dosage'=>'5mg',   'frequency'=>'OD - 1x sehari', 'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Waktu pagi'],
                        ['drug_name'=>'Atorvastatin 20mg','dosage'=>'20mg', 'frequency'=>'ON - malam',     'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Sebelum tidur'],
                    ],
                ],
                [
                    'patient' => $p2, 'doctor' => 'Dr. Faridah Noor', 'status' => 'verifying',
                    'drug_check_notes' => 'Semak interaksi Aspirin + Warfarin.',
                    'items' => [
                        ['drug_name'=>'Aspirin 75mg',  'dosage'=>'75mg',  'frequency'=>'OD', 'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Selepas sarapan'],
                        ['drug_name'=>'Rosuvastatin 10mg','dosage'=>'10mg','frequency'=>'ON','duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Sebelum tidur'],
                    ],
                ],
                [
                    'patient' => $p3, 'doctor' => 'Dr. Aiman Rashid', 'status' => 'pending',
                    'drug_check_notes' => 'Tiada interaksi kritikal dikesan.',
                    'items' => [
                        ['drug_name'=>'Salbutamol Inhaler 100mcg', 'dosage'=>'2 puff', 'frequency'=>'PRN - bila perlu', 'duration'=>'—', 'quantity'=>1, 'instructions'=>'Bila sesak nafas'],
                        ['drug_name'=>'Budesonide Inhaler 200mcg', 'dosage'=>'1 puff', 'frequency'=>'BD',               'duration'=>'30 hari', 'quantity'=>1, 'instructions'=>'Pagi & malam'],
                        ['drug_name'=>'Montelukast 10mg',           'dosage'=>'10mg',  'frequency'=>'ON',               'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Sebelum tidur'],
                        ['drug_name'=>'Cetirizine 10mg',            'dosage'=>'10mg',  'frequency'=>'OD',               'duration'=>'7 hari',  'quantity'=>7,  'instructions'=>'Waktu malam'],
                    ],
                ],
                [
                    'patient' => $p4, 'doctor' => 'Dr. Aiman Rashid', 'status' => 'ready',
                    'drug_check_notes' => 'Alahan Sulfa diambil kira. Azithromycin selamat.',
                    'items' => [
                        ['drug_name'=>'Azithromycin 500mg', 'dosage'=>'500mg', 'frequency'=>'OD', 'duration'=>'3 hari', 'quantity'=>3, 'instructions'=>'Selepas makan'],
                    ],
                ],
                [
                    'patient' => $p5, 'doctor' => 'Dr. Aiman Rashid', 'status' => 'dispensed',
                    'drug_check_notes' => 'Tiada interaksi kritikal dikesan.',
                    'dispensed_at' => now()->subHours(2),
                    'dispensed_by' => 'Mr. Vinod',
                    'items' => [
                        ['drug_name'=>'Levothyroxine 50mcg', 'dosage'=>'50mcg', 'frequency'=>'OD', 'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'30 minit sebelum sarapan'],
                    ],
                ],
            ];

            foreach ($rxData as $d) {
                if (! $d['patient']) continue;
                $rx = Prescription::create([
                    'patient_id'          => $d['patient']->id,
                    'prescribing_doctor'  => $d['doctor'],
                    'status'              => $d['status'],
                    'notes'               => $d['notes'] ?? null,
                    'drug_check_passed'   => true,
                    'drug_check_notes'    => $d['drug_check_notes'] ?? null,
                    'dispensed_at'        => $d['dispensed_at'] ?? null,
                    'dispensed_by'        => $d['dispensed_by'] ?? null,
                ]);
                foreach ($d['items'] as $item) {
                    $rx->items()->create($item);
                }
            }
        }

        // Sample appointments for current week
        if (Appointment::count() === 0) {
            $docName = 'Dr. Aiman Rashid';
            $mon  = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $tue  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDay()->format('Y-m-d');
            $wed  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(2)->format('Y-m-d');
            $thu  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(3)->format('Y-m-d');
            $fri  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(4)->format('Y-m-d');
            $sat  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(5)->format('Y-m-d');

            $pt = [];
            foreach (['780229-08-5234','650412-14-8821','920815-10-7733','850101-14-5678','010322-08-1145','950707-03-9988','750314-08-1122','880905-10-4477'] as $ic) {
                $pt[] = Patient::where('ic_number', $ic)->first();
            }

            $appts = [
                // Monday
                ['date'=>$mon,'time'=>'08:00','patient'=>$pt[0],'type'=>'follow_up',      'status'=>'done',      'reason'=>'Kawalan tekanan darah',        'duration'=>30],
                ['date'=>$mon,'time'=>'08:30','patient'=>$pt[1],'type'=>'follow_up',      'status'=>'done',      'reason'=>'Kawalan kolesterol',            'duration'=>30],
                ['date'=>$mon,'time'=>'09:00','patient'=>$pt[2],'type'=>'new',            'status'=>'done',      'reason'=>'Sesak nafas berulang',          'duration'=>30],
                ['date'=>$mon,'time'=>'09:30','patient'=>$pt[3],'type'=>'annual_checkup', 'status'=>'cancelled', 'reason'=>'Pemeriksaan tahunan',           'duration'=>30],
                ['date'=>$mon,'time'=>'10:00','patient'=>$pt[4],'type'=>'new',            'status'=>'done',      'reason'=>'Demam dan sakit tekak',         'duration'=>15],
                // Tuesday
                ['date'=>$tue,'time'=>'08:00','patient'=>$pt[5],'type'=>'antenatal',      'status'=>'confirmed', 'reason'=>'Antenatal minggu ke-24',        'duration'=>30],
                ['date'=>$tue,'time'=>'08:30','patient'=>$pt[6],'type'=>'follow_up',      'status'=>'waiting',   'reason'=>'Kawalan hipertensi',            'duration'=>30],
                ['date'=>$tue,'time'=>'09:00','patient'=>$pt[7],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Tiroid susulan',                'duration'=>30],
                ['date'=>$tue,'time'=>'10:00','patient'=>$pt[0],'type'=>'procedure',      'status'=>'confirmed', 'reason'=>'Ambil darah HbA1c',             'duration'=>15],
                // Wednesday
                ['date'=>$wed,'time'=>'08:00','patient'=>$pt[2],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Semakan ubat asma',             'duration'=>30],
                ['date'=>$wed,'time'=>'09:00','patient'=>$pt[3],'type'=>'new',            'status'=>'confirmed', 'reason'=>'Sakit kepala kronik',           'duration'=>30],
                ['date'=>$wed,'time'=>'10:00','patient'=>$pt[1],'type'=>'teleconsult',    'status'=>'confirmed', 'reason'=>'Teleperubatan bulanan',         'duration'=>30],
                ['date'=>$wed,'time'=>'11:00','patient'=>$pt[4],'type'=>'follow_up',      'status'=>'waiting',   'reason'=>'Susulan selepas rawatan',       'duration'=>15],
                // Thursday
                ['date'=>$thu,'time'=>'08:30','patient'=>$pt[5],'type'=>'antenatal',      'status'=>'confirmed', 'reason'=>'Antenatal — scan 20 minggu',   'duration'=>45],
                ['date'=>$thu,'time'=>'09:30','patient'=>$pt[6],'type'=>'annual_checkup', 'status'=>'confirmed', 'reason'=>'Pemeriksaan tahunan',           'duration'=>30],
                ['date'=>$thu,'time'=>'10:30','patient'=>$pt[7],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Kawalan tiroid',                'duration'=>30],
                // Friday
                ['date'=>$fri,'time'=>'08:00','patient'=>$pt[0],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Semakan ubat DM + HTN',         'duration'=>30],
                ['date'=>$fri,'time'=>'09:00','patient'=>$pt[2],'type'=>'procedure',      'status'=>'confirmed', 'reason'=>'Ujian fungsi paru-paru',        'duration'=>30],
                ['date'=>$fri,'time'=>'10:00','patient'=>$pt[3],'type'=>'new',            'status'=>'no_show',   'reason'=>'Ruam kulit berulang',           'duration'=>30],
                // Saturday
                ['date'=>$sat,'time'=>'08:00','patient'=>$pt[1],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Tekanan darah tinggi',          'duration'=>30],
                ['date'=>$sat,'time'=>'08:30','patient'=>$pt[4],'type'=>'new',            'status'=>'confirmed', 'reason'=>'Batuk berlarutan',              'duration'=>30],
            ];

            foreach ($appts as $a) {
                if (! $a['patient']) continue;
                Appointment::create([
                    'patient_id'       => $a['patient']->id,
                    'doctor_name'      => $docName,
                    'appointment_date' => $a['date'],
                    'appointment_time' => $a['time'],
                    'duration_minutes' => $a['duration'],
                    'type'             => $a['type'],
                    'reason'           => $a['reason'],
                    'status'           => $a['status'],
                ]);
            }
        }

        // Sample EMR visits
        if (Visit::count() === 0) {
            $doc = 'Dr. Aiman Rashid';
            $p1  = Patient::where('ic_number', '780229-08-5234')->first(); // Aminah HTN+T2DM
            $p2  = Patient::where('ic_number', '650412-14-8821')->first(); // Lim CAD
            $p3  = Patient::where('ic_number', '920815-10-7733')->first(); // Tan Asthma
            $p5  = Patient::where('ic_number', '010322-08-1145')->first(); // Rajesh
            $p6  = Patient::where('ic_number', '950707-03-9988')->first(); // Nurul Antenatal

            // Visit 1 — Closed, Aminah (2 days ago)
            if ($p1) {
                $v1 = Visit::create([
                    'patient_id'      => $p1->id,
                    'doctor_name'     => $doc,
                    'visit_date'      => now()->subDays(2)->format('Y-m-d'),
                    'chief_complaint' => 'Kawalan tekanan darah dan gula darah bulanan',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit hadir untuk kawalan rutin. Menyatakan tekanan darah telah stabil minggu ini. Tiada pening kepala atau sesak nafas. Pematuhan ubat baik. Makan mengikut diet.",
                    'soap_o'          => "BP: 138/86 mmHg\nHR: 78 bpm\nTemp: 36.8°C\nSpO2: 98%\nBerat: 67.5 kg\nGula darah rawak: 8.2 mmol/L",
                    'soap_a'          => "1. Hipertensi Penting — terkawal sederhana\n2. Diabetes Mellitus Jenis 2 — kawalan sederhana (HbA1c 7.4% bulan lalu)",
                    'soap_p'          => "1. Teruskan Amlodipine 5mg OD\n2. Teruskan Metformin 500mg BD\n3. Semak semula 4 minggu\n4. Rujuk pemakanan jika HbA1c >8% semasa semakan seterusnya",
                    'signed_at'       => now()->subDays(2)->setTime(10, 30),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v1->id,'bp_systolic'=>138,'bp_diastolic'=>86,'heart_rate'=>78,'temperature'=>36.8,'spo2'=>98,'weight'=>67.5,'height'=>158]);
                VisitDiagnosis::create(['visit_id'=>$v1->id,'icd_code'=>'I10','description'=>'Hipertensi penting','type'=>'primary']);
                VisitDiagnosis::create(['visit_id'=>$v1->id,'icd_code'=>'E11.9','description'=>'Diabetes Mellitus Jenis 2 tanpa komplikasi','type'=>'secondary']);
            }

            // Visit 2 — Closed, Tan Wei Ming (3 days ago)
            if ($p3) {
                $v2 = Visit::create([
                    'patient_id'      => $p3->id,
                    'doctor_name'     => $doc,
                    'visit_date'      => now()->subDays(3)->format('Y-m-d'),
                    'chief_complaint' => 'Sesak nafas berulang dan batuk malam',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit mengadu sesak nafas berulang terutama pada waktu malam dan pagi. Batuk kering sejak 1 minggu. Terdedah kepada asap rokok di tempat kerja. Menggunakan inhaler salbutamol 2-3 kali seminggu.",
                    'soap_o'          => "BP: 118/76 mmHg\nHR: 84 bpm\nTemp: 36.9°C\nSpO2: 96%\nBerat: 72 kg\nWheeze bilateral semasa auskultasi",
                    'soap_a'          => "Asma bronkial — kurang terkawal (GINA Step 2)",
                    'soap_p'          => "1. Mulakan Budesonide 200mcg inhaler BD\n2. Teruskan Salbutamol PRN\n3. Nasihat elak pencetus\n4. Semak semula 2 minggu\n5. Rujuk hospital jika tidak baik",
                    'signed_at'       => now()->subDays(3)->setTime(9, 15),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v2->id,'bp_systolic'=>118,'bp_diastolic'=>76,'heart_rate'=>84,'temperature'=>36.9,'spo2'=>96,'weight'=>72.0,'height'=>172]);
                VisitDiagnosis::create(['visit_id'=>$v2->id,'icd_code'=>'J45.9','description'=>'Asma bronkial, tidak spesifik','type'=>'primary']);
            }

            // Visit 3 — Open, Lim Kok Wing (today)
            if ($p2) {
                $v3 = Visit::create([
                    'patient_id'      => $p2->id,
                    'doctor_name'     => $doc,
                    'visit_date'      => now()->format('Y-m-d'),
                    'chief_complaint' => 'Sakit dada dan berdebar-debar',
                    'status'          => 'open',
                    'soap_s'          => "Pesakit hadir dengan aduan sakit dada tekanan sejak 2 jam. Sakit menjalar ke lengan kiri. Berdebar-debar. Ada riwayat CAD dan dyslipidemia. Ubat terkini: Aspirin 75mg OD, Rosuvastatin 10mg ON.",
                    'soap_o'          => "BP: 156/94 mmHg\nHR: 96 bpm\nTemp: 37.1°C\nSpO2: 97%\nBerat: 78 kg\nECG: ST depression V4-V6",
                    'soap_a'          => null,
                    'soap_p'          => null,
                ]);
                VisitVital::create(['visit_id'=>$v3->id,'bp_systolic'=>156,'bp_diastolic'=>94,'heart_rate'=>96,'temperature'=>37.1,'spo2'=>97,'weight'=>78.0,'height'=>168]);
                VisitDiagnosis::create(['visit_id'=>$v3->id,'icd_code'=>'I20.0','description'=>'Angina tidak stabil','type'=>'primary']);
                VisitDiagnosis::create(['visit_id'=>$v3->id,'icd_code'=>'E78.5','description'=>'Hiperlipidemia campuran','type'=>'secondary']);
            }

            // Visit 4 — Open, Rajesh (today)
            if ($p5) {
                $v4 = Visit::create([
                    'patient_id'      => $p5->id,
                    'doctor_name'     => $doc,
                    'visit_date'      => now()->format('Y-m-d'),
                    'chief_complaint' => 'Demam tinggi dan sakit tekak 3 hari',
                    'status'          => 'open',
                    'soap_s'          => "Pesakit berusia 25 tahun hadir dengan demam tinggi maksimum 39.2°C, sakit tekak teruk, dan susah menelan sejak 3 hari. Tiada batuk. Tiada cirit-birit. Selera makan berkurang. Alahan Sulfa diketahui.",
                    'soap_o'          => "BP: 110/70 mmHg\nHR: 102 bpm\nTemp: 38.8°C\nSpO2: 99%\nBerat: 68 kg\nTonsil: exudate bilateral, cervical lymphadenopathy",
                    'soap_a'          => 'Faringitis akut — kemungkinan Group A Streptococcus',
                    'soap_p'          => null,
                ]);
                VisitVital::create(['visit_id'=>$v4->id,'bp_systolic'=>110,'bp_diastolic'=>70,'heart_rate'=>102,'temperature'=>38.8,'spo2'=>99,'weight'=>68.0,'height'=>175]);
                VisitDiagnosis::create(['visit_id'=>$v4->id,'icd_code'=>'J02.0','description'=>'Faringitis streptokokal','type'=>'primary']);
            }

            // Visit 5 — Closed, Nurul Antenatal
            if ($p6) {
                $v5 = Visit::create([
                    'patient_id'      => $p6->id,
                    'doctor_name'     => $doc,
                    'visit_date'      => now()->subDays(7)->format('Y-m-d'),
                    'chief_complaint' => 'Pemeriksaan antenatal mingguan ke-24',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit G1P0, 24 minggu mengandung. Rasa pergerakan janin aktif. Tiada pendarahan per vaginam. Tiada sakit perut. Mual pada waktu pagi berkurang. Pematuhan folate dan iron baik.",
                    'soap_o'          => "BP: 112/72 mmHg\nHR: 82 bpm\nTemp: 36.7°C\nSpO2: 99%\nBerat: 58 kg (naik 1.2kg)\nFH: 24 cm, FHR: 148 bpm\nOedema: tiada",
                    'soap_a'          => "Kehamilan normal 24 minggu. Perkembangan janin baik.",
                    'soap_p'          => "1. Teruskan Folic Acid 5mg OD\n2. Teruskan Ferrous Fumarate 200mg BD\n3. Scan anatomi 20 minggu — normal\n4. OGTT dijadualkan minggu 28\n5. Semak semula 4 minggu",
                    'signed_at'       => now()->subDays(7)->setTime(11, 0),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v5->id,'bp_systolic'=>112,'bp_diastolic'=>72,'heart_rate'=>82,'temperature'=>36.7,'spo2'=>99,'weight'=>58.0,'height'=>160]);
                VisitDiagnosis::create(['visit_id'=>$v5->id,'icd_code'=>'Z34.2','description'=>'Pengawasan kehamilan normal trimester kedua','type'=>'primary']);
            }
        }

        // Sample invoices
        if (Invoice::count() === 0) {
            $p1 = Patient::where('ic_number', '780229-08-5234')->first(); // Aminah
            $p2 = Patient::where('ic_number', '650412-14-8821')->first(); // Lim Kok Wing
            $p3 = Patient::where('ic_number', '920815-10-7733')->first(); // Tan Wei Ming
            $p4 = Patient::where('ic_number', '010322-08-1145')->first(); // Rajesh Kumar
            $p5 = Patient::where('ic_number', '950707-03-9988')->first(); // Nurul Ain

            $mkInv = function (Patient $pt, string $date, string $status, ?string $method, ?string $paidAt, array $items, float $discount = 0) {
                $inv = Invoice::create([
                    'patient_id'      => $pt->id,
                    'invoice_date'    => $date,
                    'status'          => $status,
                    'payment_method'  => $method,
                    'discount_amount' => $discount,
                    'paid_at'         => $paidAt ? \Carbon\Carbon::parse($paidAt) : null,
                    'paid_by'         => $paidAt ? 'Dr. Aiman Rashid' : null,
                ]);
                foreach ($items as $it) {
                    InvoiceItem::create(array_merge(['invoice_id' => $inv->id, 'total_price' => round($it['quantity'] * $it['unit_price'], 2)], $it));
                }
                $inv->recalc();
                return $inv;
            };

            // INV 1 — Aminah, paid cash
            if ($p1) $mkInv($p1, now()->subDays(2)->format('Y-m-d'), 'paid', 'cash', now()->subDays(2)->setTime(10,45)->toDateTimeString(), [
                ['type'=>'consultation','code'=>'CONS-001','description'=>'Rawatan GP — Dr. Aiman Rashid',    'quantity'=>1,  'unit_price'=>35.00],
                ['type'=>'procedure',   'code'=>'PROC-001','description'=>'Ambil Darah (FBC + HbA1c)',        'quantity'=>1,  'unit_price'=>25.00],
                ['type'=>'drug',        'code'=>'DRUG-MET','description'=>'Metformin 500mg × 60 tab',         'quantity'=>60, 'unit_price'=>0.18],
                ['type'=>'drug',        'code'=>'DRUG-AML','description'=>'Amlodipine 5mg × 30 tab',          'quantity'=>30, 'unit_price'=>0.22],
            ]);

            // INV 2 — Tan Wei Ming, paid card
            if ($p3) $mkInv($p3, now()->subDays(3)->format('Y-m-d'), 'paid', 'card', now()->subDays(3)->setTime(9,30)->toDateTimeString(), [
                ['type'=>'consultation','code'=>'CONS-001','description'=>'Rawatan GP — Dr. Aiman Rashid',    'quantity'=>1, 'unit_price'=>35.00],
                ['type'=>'procedure',   'code'=>'PROC-005','description'=>'Ujian Fungsi Paru-paru (Spirometry)','quantity'=>1, 'unit_price'=>25.00],
                ['type'=>'drug',        'code'=>'DRUG-SAL','description'=>'Salbutamol Inhaler 100mcg',        'quantity'=>1, 'unit_price'=>18.00],
                ['type'=>'drug',        'code'=>'DRUG-BUD','description'=>'Budesonide Inhaler 200mcg',        'quantity'=>1, 'unit_price'=>32.50],
            ]);

            // INV 3 — Rajesh Kumar, paid DuitNow
            if ($p4) $mkInv($p4, now()->subDays(1)->format('Y-m-d'), 'paid', 'duitnow', now()->subDays(1)->setTime(14,20)->toDateTimeString(), [
                ['type'=>'consultation','code'=>'CONS-001','description'=>'Rawatan GP — Dr. Aiman Rashid',    'quantity'=>1, 'unit_price'=>35.00],
                ['type'=>'lab',         'code'=>'LAB-RAT', 'description'=>'Rapid Antigen Test (COVID-19)',    'quantity'=>1, 'unit_price'=>25.00],
                ['type'=>'drug',        'code'=>'DRUG-AZT','description'=>'Azithromycin 500mg × 3 tab',       'quantity'=>3, 'unit_price'=>1.20],
                ['type'=>'drug',        'code'=>'DRUG-PCM','description'=>'Paracetamol 1g × 10 tab',          'quantity'=>10,'unit_price'=>0.20],
            ], 5.00);

            // INV 4 — Lim Kok Wing, unpaid (panel)
            if ($p2) $mkInv($p2, now()->format('Y-m-d'), 'unpaid', 'panel', null, [
                ['type'=>'consultation','code'=>'CONS-001','description'=>'Rawatan GP — Dr. Aiman Rashid',    'quantity'=>1, 'unit_price'=>35.00],
                ['type'=>'procedure',   'code'=>'PROC-ECG','description'=>'Elektrokardiogram (ECG)',          'quantity'=>1, 'unit_price'=>25.00],
                ['type'=>'lab',         'code'=>'LAB-TRO', 'description'=>'Troponin I Rapid Test',            'quantity'=>1, 'unit_price'=>45.00],
            ]);

            // INV 5 — Nurul Ain, draft
            if ($p5) $mkInv($p5, now()->format('Y-m-d'), 'draft', null, null, [
                ['type'=>'consultation','code'=>'CONS-ANC','description'=>'Lawatan Antenatal — Dr. Aiman Rashid','quantity'=>1, 'unit_price'=>45.00],
                ['type'=>'drug',        'code'=>'DRUG-FOL','description'=>'Asid Folik 5mg × 30 tab',           'quantity'=>30,'unit_price'=>0.15],
                ['type'=>'drug',        'code'=>'DRUG-FER','description'=>'Ferus Fumarat 200mg × 60 tab',       'quantity'=>60,'unit_price'=>0.12],
            ]);

            // INV 6 — Aminah older paid invoice (last week)
            if ($p1) $mkInv($p1, now()->subDays(30)->format('Y-m-d'), 'paid', 'cash', now()->subDays(30)->setTime(9,0)->toDateTimeString(), [
                ['type'=>'consultation','code'=>'CONS-001','description'=>'Rawatan GP — Dr. Aiman Rashid',    'quantity'=>1, 'unit_price'=>35.00],
                ['type'=>'drug',        'code'=>'DRUG-MET','description'=>'Metformin 500mg × 60 tab',         'quantity'=>60,'unit_price'=>0.18],
                ['type'=>'drug',        'code'=>'DRUG-AML','description'=>'Amlodipine 5mg × 30 tab',          'quantity'=>30,'unit_price'=>0.22],
                ['type'=>'drug',        'code'=>'DRUG-ATV','description'=>'Atorvastatin 20mg × 30 tab',        'quantity'=>30,'unit_price'=>0.45],
            ]);
        }

        // Inventory items
        if (InventoryItem::count() === 0) {
            $inventory = [
                ['name'=>'Amoxicillin 500mg',         'generic_name'=>'Amoxicillin',       'form'=>'Kapsul',    'category'=>'Antibiotik',    'classification'=>'general',   'lot_number'=>'AMX-2403', 'expiry_date'=>'2027-08-31', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>240,  'reorder_level'=>200, 'unit_cost'=>0.30,  'unit'=>'kapsul'],
                ['name'=>'Paracetamol 1g',             'generic_name'=>'Paracetamol',        'form'=>'Tablet',    'category'=>'Analgesik',     'classification'=>'general',   'lot_number'=>'PCM-2502', 'expiry_date'=>'2028-03-31', 'supplier'=>'Duopharma',   'stock_quantity'=>1840, 'reorder_level'=>500, 'unit_cost'=>0.20,  'unit'=>'tablet'],
                ['name'=>'Salbutamol Inhaler 100mcg',  'generic_name'=>'Salbutamol',         'form'=>'MDI',       'category'=>'Pernafasan',    'classification'=>'general',   'lot_number'=>'SAL-2511', 'expiry_date'=>'2027-11-30', 'supplier'=>'GSK',         'stock_quantity'=>8,    'reorder_level'=>25,  'unit_cost'=>18.00, 'unit'=>'inhaler'],
                ['name'=>'Metformin 500mg',            'generic_name'=>'Metformin HCl',      'form'=>'Tablet',    'category'=>'Antidiabetik',  'classification'=>'general',   'lot_number'=>'MET-2402', 'expiry_date'=>'2027-02-28', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>32,   'reorder_level'=>200, 'unit_cost'=>0.18,  'unit'=>'tablet'],
                ['name'=>'Codeine Phosphate 30mg',     'generic_name'=>'Codeine Phosphate',  'form'=>'Tablet',    'category'=>'Analgesik',     'classification'=>'poison_c',  'lot_number'=>'COD-2509', 'expiry_date'=>'2026-09-30', 'supplier'=>'Hovid',       'stock_quantity'=>120,  'reorder_level'=>50,  'unit_cost'=>0.85,  'unit'=>'tablet'],
                ['name'=>'Diazepam 5mg',               'generic_name'=>'Diazepam',           'form'=>'Tablet',    'category'=>'Saraf',         'classification'=>'poison_b',  'lot_number'=>'DIZ-2412', 'expiry_date'=>'2026-12-31', 'supplier'=>'Duopharma',   'stock_quantity'=>60,   'reorder_level'=>30,  'unit_cost'=>0.95,  'unit'=>'tablet'],
                ['name'=>'Ciprofloxacin 500mg',        'generic_name'=>'Ciprofloxacin',      'form'=>'Tablet',    'category'=>'Antibiotik',    'classification'=>'general',   'lot_number'=>'CIP-2206', 'expiry_date'=>'2026-06-30', 'supplier'=>'Hexpharm',    'stock_quantity'=>28,   'reorder_level'=>100, 'unit_cost'=>0.65,  'unit'=>'tablet'],
                ['name'=>'Amlodipine 5mg',             'generic_name'=>'Amlodipine',         'form'=>'Tablet',    'category'=>'Antihipertensi','classification'=>'general',   'lot_number'=>'AML-2501', 'expiry_date'=>'2027-06-30', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>350,  'reorder_level'=>100, 'unit_cost'=>0.22,  'unit'=>'tablet'],
                ['name'=>'Atorvastatin 20mg',          'generic_name'=>'Atorvastatin',       'form'=>'Tablet',    'category'=>'Kardiologi',    'classification'=>'general',   'lot_number'=>'ATV-2503', 'expiry_date'=>'2028-01-31', 'supplier'=>'Pfizer',      'stock_quantity'=>420,  'reorder_level'=>100, 'unit_cost'=>0.45,  'unit'=>'tablet'],
                ['name'=>'Losartan 50mg',              'generic_name'=>'Losartan Potassium', 'form'=>'Tablet',    'category'=>'Antihipertensi','classification'=>'general',   'lot_number'=>'LOS-2504', 'expiry_date'=>'2027-09-30', 'supplier'=>'Duopharma',   'stock_quantity'=>280,  'reorder_level'=>100, 'unit_cost'=>0.35,  'unit'=>'tablet'],
                ['name'=>'Omeprazole 20mg',            'generic_name'=>'Omeprazole',         'form'=>'Kapsul',    'category'=>'Gastroenterologi','classification'=>'general', 'lot_number'=>'OMP-2505', 'expiry_date'=>'2027-12-31', 'supplier'=>'Hovid',       'stock_quantity'=>560,  'reorder_level'=>150, 'unit_cost'=>0.28,  'unit'=>'kapsul'],
                ['name'=>'Levothyroxine 50mcg',        'generic_name'=>'Levothyroxine',      'form'=>'Tablet',    'category'=>'Hormon',        'classification'=>'general',   'lot_number'=>'LEV-2506', 'expiry_date'=>'2027-04-30', 'supplier'=>'Merck',       'stock_quantity'=>180,  'reorder_level'=>60,  'unit_cost'=>0.55,  'unit'=>'tablet'],
                ['name'=>'Azithromycin 500mg',         'generic_name'=>'Azithromycin',       'form'=>'Tablet',    'category'=>'Antibiotik',    'classification'=>'general',   'lot_number'=>'AZT-2507', 'expiry_date'=>'2027-10-31', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>75,   'reorder_level'=>50,  'unit_cost'=>1.20,  'unit'=>'tablet'],
                ['name'=>'Budesonide Inhaler 200mcg',  'generic_name'=>'Budesonide',         'form'=>'MDI',       'category'=>'Pernafasan',    'classification'=>'general',   'lot_number'=>'BUD-2508', 'expiry_date'=>'2027-07-31', 'supplier'=>'AstraZeneca', 'stock_quantity'=>14,   'reorder_level'=>20,  'unit_cost'=>32.50, 'unit'=>'inhaler'],
                ['name'=>'Cetirizine 10mg',            'generic_name'=>'Cetirizine HCl',     'form'=>'Tablet',    'category'=>'Antihistamin',  'classification'=>'general',   'lot_number'=>'CET-2510', 'expiry_date'=>'2028-05-31', 'supplier'=>'Duopharma',   'stock_quantity'=>320,  'reorder_level'=>100, 'unit_cost'=>0.15,  'unit'=>'tablet'],
                ['name'=>'Morphine Sulphate 10mg',     'generic_name'=>'Morphine Sulphate',  'form'=>'Tablet',    'category'=>'Analgesik',     'classification'=>'controlled','lot_number'=>'MOR-2501', 'expiry_date'=>'2026-08-31', 'supplier'=>'Hospira',     'stock_quantity'=>30,   'reorder_level'=>20,  'unit_cost'=>4.50,  'unit'=>'tablet', 'notes'=>'Stok kawalan — rekod wajib'],
            ];

            foreach ($inventory as $data) {
                $item = InventoryItem::create(array_merge($data, ['status' => 'active']));
                // Opening stock transaction
                $item->transactions()->create([
                    'type'           => 'in',
                    'quantity_delta' => $item->stock_quantity,
                    'quantity_after' => $item->stock_quantity,
                    'reference'      => 'Stok awal',
                    'performed_by'   => 'System',
                ]);
            }
        }
    }
}
