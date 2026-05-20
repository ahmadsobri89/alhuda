<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SecurityPolicy;
use App\Models\User;
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
