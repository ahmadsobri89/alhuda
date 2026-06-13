<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ClinicProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryItem;
use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\QuarantineLetter;
use App\Models\ReferralLetter;
use App\Models\SecurityPolicy;
use App\Models\TimeSlip;
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
        $this->call(LookupSeeder::class);
        $this->call(AppointmentEmrSeeder::class);

        // ── Staff ─────────────────────────────────────────────
        $staff = [
            ['name' => 'Dr. Aiman Rashid', 'role' => 'doctor',      'mmc_number' => 'MMC-87231', 'email' => 'aiman@alhuda.my',   'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Nurse Fatimah',    'role' => 'nurse',        'mmc_number' => null,        'email' => 'fatimah@alhuda.my', 'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Encik Zahid',      'role' => 'admin',        'mmc_number' => null,        'email' => 'zahid@alhuda.my',   'mfa_enabled' => true,  'status' => 'active'],
            ['name' => 'Pn. Salina',       'role' => 'receptionist', 'mmc_number' => null,        'email' => 'salina@alhuda.my',  'mfa_enabled' => false, 'status' => 'active'],
            ['name' => 'Mr. Vinod',        'role' => 'pharmacist',   'mmc_number' => null,        'email' => 'vinod@alhuda.my',   'mfa_enabled' => true,  'status' => 'inactive'],
        ];
        foreach ($staff as $s) {
            User::updateOrCreate(['email' => $s['email']], array_merge($s, ['password' => Hash::make('password'), 'email_verified_at' => now()]));
        }
        User::updateOrCreate(['email' => 'test@alhuda.my'], [
            'name' => 'Dr. Aiman Rashid', 'role' => 'doctor', 'mmc_number' => 'MMC-87231',
            'mfa_enabled' => true, 'status' => 'active',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);

        // ── Security policies ──────────────────────────────────
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

        // ── Patients ───────────────────────────────────────────
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
                'blood_type' => 'AB+', 'allergies' => null, 'conditions' => [],
                'emergency_contact_name' => 'Mohd Farid', 'emergency_contact_phone' => '014-7788990',
                'visit_count' => 3, 'last_visit_at' => now()->subDay(),
            ],
            [
                'name' => 'Rajesh Kumar', 'ic_number' => '010322-08-1145',
                'date_of_birth' => '2001-03-22', 'gender' => 'male',
                'phone' => '016-3344221', 'email' => null,
                'address' => 'No. 22, Jalan Utama', 'postcode' => '47500', 'city' => 'Subang Jaya', 'state' => 'Selangor',
                'blood_type' => 'O-', 'allergies' => 'Sulfa', 'conditions' => [],
                'emergency_contact_name' => 'Kumar Pillai', 'emergency_contact_phone' => '012-8899001',
                'visit_count' => 2, 'last_visit_at' => now()->subDay(),
            ],
            [
                'name' => 'Nurul Ain Zainal', 'ic_number' => '950707-03-9988',
                'date_of_birth' => '1995-07-07', 'gender' => 'female',
                'phone' => '013-2233998', 'email' => 'nurulain@gmail.com',
                'address' => 'No. 4, Persiaran Damai', 'postcode' => '43650', 'city' => 'Bangi', 'state' => 'Selangor',
                'blood_type' => 'A-', 'allergies' => null, 'conditions' => ['Pregnancy W24'],
                'emergency_contact_name' => 'Zainal Abidin', 'emergency_contact_phone' => '019-5544332',
                'visit_count' => 8, 'last_visit_at' => now()->subDays(2),
            ],
            [
                'name' => 'Hassan bin Ali', 'ic_number' => '750314-08-1122',
                'date_of_birth' => '1975-03-14', 'gender' => 'male',
                'phone' => '012-9988776', 'email' => null,
                'address' => 'No. 77, Taman Maju', 'postcode' => '43000', 'city' => 'Kajang', 'state' => 'Selangor',
                'blood_type' => 'B-', 'allergies' => null, 'conditions' => ['HTN'],
                'emergency_contact_name' => 'Fatimah binti Yusof', 'emergency_contact_phone' => '011-6677889',
                'visit_count' => 18, 'last_visit_at' => now()->subDays(8),
            ],
            [
                'name' => 'Wong Mei Ling', 'ic_number' => '880905-10-4477',
                'date_of_birth' => '1988-09-05', 'gender' => 'female',
                'phone' => '018-7766554', 'email' => 'meilingwong@gmail.com',
                'address' => 'No. 33, Jalan Puteri', 'postcode' => '47810', 'city' => 'Petaling Jaya', 'state' => 'Selangor',
                'blood_type' => 'AB-', 'allergies' => null, 'conditions' => ['Hypothyroid'],
                'emergency_contact_name' => 'Wong Ah Beng', 'emergency_contact_phone' => '013-1122334',
                'visit_count' => 5, 'last_visit_at' => now()->subDays(10),
            ],
        ];
        foreach ($patients as $p) {
            Patient::updateOrCreate(['ic_number' => $p['ic_number']], $p);
        }

        // ── Inventory ──────────────────────────────────────────
        if (InventoryItem::count() === 0) {
            $inventory = [
                ['name'=>'Amoxicillin 500mg',         'generic_name'=>'Amoxicillin',         'form'=>'Kapsul',  'category'=>'Antibiotik',       'classification'=>'general',    'lot_number'=>'AMX-2403', 'expiry_date'=>'2027-08-31', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>240,  'reorder_level'=>200, 'unit_cost'=>0.30,  'selling_price'=>0.90,  'unit'=>'kapsul'],
                ['name'=>'Paracetamol 1g',             'generic_name'=>'Paracetamol',         'form'=>'Tablet',  'category'=>'Analgesik',        'classification'=>'general',    'lot_number'=>'PCM-2502', 'expiry_date'=>'2028-03-31', 'supplier'=>'Duopharma',   'stock_quantity'=>1840, 'reorder_level'=>500, 'unit_cost'=>0.20,  'selling_price'=>0.50,  'unit'=>'tablet'],
                ['name'=>'Salbutamol Inhaler 100mcg',  'generic_name'=>'Salbutamol',          'form'=>'MDI',     'category'=>'Pernafasan',       'classification'=>'general',    'lot_number'=>'SAL-2511', 'expiry_date'=>'2027-11-30', 'supplier'=>'GSK',         'stock_quantity'=>8,    'reorder_level'=>25,  'unit_cost'=>18.00, 'selling_price'=>28.00, 'unit'=>'inhaler'],
                ['name'=>'Metformin 500mg',            'generic_name'=>'Metformin HCl',       'form'=>'Tablet',  'category'=>'Antidiabetik',     'classification'=>'general',    'lot_number'=>'MET-2402', 'expiry_date'=>'2027-02-28', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>32,   'reorder_level'=>200, 'unit_cost'=>0.18,  'selling_price'=>0.40,  'unit'=>'tablet'],
                ['name'=>'Codeine Phosphate 30mg',     'generic_name'=>'Codeine Phosphate',   'form'=>'Tablet',  'category'=>'Analgesik',        'classification'=>'poison_c',   'lot_number'=>'COD-2509', 'expiry_date'=>'2026-09-30', 'supplier'=>'Hovid',       'stock_quantity'=>120,  'reorder_level'=>50,  'unit_cost'=>0.85,  'selling_price'=>2.50,  'unit'=>'tablet'],
                ['name'=>'Diazepam 5mg',               'generic_name'=>'Diazepam',            'form'=>'Tablet',  'category'=>'Saraf',            'classification'=>'poison_b',   'lot_number'=>'DIZ-2412', 'expiry_date'=>'2026-12-31', 'supplier'=>'Duopharma',   'stock_quantity'=>60,   'reorder_level'=>30,  'unit_cost'=>0.95,  'selling_price'=>2.80,  'unit'=>'tablet'],
                ['name'=>'Ciprofloxacin 500mg',        'generic_name'=>'Ciprofloxacin',       'form'=>'Tablet',  'category'=>'Antibiotik',       'classification'=>'general',    'lot_number'=>'CIP-2206', 'expiry_date'=>'2026-06-30', 'supplier'=>'Hexpharm',    'stock_quantity'=>28,   'reorder_level'=>100, 'unit_cost'=>0.65,  'selling_price'=>1.80,  'unit'=>'tablet'],
                ['name'=>'Amlodipine 5mg',             'generic_name'=>'Amlodipine',          'form'=>'Tablet',  'category'=>'Antihipertensi',   'classification'=>'general',    'lot_number'=>'AML-2501', 'expiry_date'=>'2027-06-30', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>350,  'reorder_level'=>100, 'unit_cost'=>0.22,  'selling_price'=>0.65,  'unit'=>'tablet'],
                ['name'=>'Atorvastatin 20mg',          'generic_name'=>'Atorvastatin',        'form'=>'Tablet',  'category'=>'Kardiologi',       'classification'=>'general',    'lot_number'=>'ATV-2503', 'expiry_date'=>'2028-01-31', 'supplier'=>'Pfizer',      'stock_quantity'=>420,  'reorder_level'=>100, 'unit_cost'=>0.45,  'selling_price'=>1.20,  'unit'=>'tablet'],
                ['name'=>'Losartan 50mg',              'generic_name'=>'Losartan Potassium',  'form'=>'Tablet',  'category'=>'Antihipertensi',   'classification'=>'general',    'lot_number'=>'LOS-2504', 'expiry_date'=>'2027-09-30', 'supplier'=>'Duopharma',   'stock_quantity'=>280,  'reorder_level'=>100, 'unit_cost'=>0.35,  'selling_price'=>0.90,  'unit'=>'tablet'],
                ['name'=>'Omeprazole 20mg',            'generic_name'=>'Omeprazole',          'form'=>'Kapsul',  'category'=>'Gastroenterologi', 'classification'=>'general',    'lot_number'=>'OMP-2505', 'expiry_date'=>'2027-12-31', 'supplier'=>'Hovid',       'stock_quantity'=>560,  'reorder_level'=>150, 'unit_cost'=>0.28,  'selling_price'=>0.80,  'unit'=>'kapsul'],
                ['name'=>'Levothyroxine 50mcg',        'generic_name'=>'Levothyroxine',       'form'=>'Tablet',  'category'=>'Hormon',           'classification'=>'general',    'lot_number'=>'LEV-2506', 'expiry_date'=>'2027-04-30', 'supplier'=>'Merck',       'stock_quantity'=>180,  'reorder_level'=>60,  'unit_cost'=>0.55,  'selling_price'=>1.50,  'unit'=>'tablet'],
                ['name'=>'Azithromycin 500mg',         'generic_name'=>'Azithromycin',        'form'=>'Tablet',  'category'=>'Antibiotik',       'classification'=>'general',    'lot_number'=>'AZT-2507', 'expiry_date'=>'2027-10-31', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>75,   'reorder_level'=>50,  'unit_cost'=>1.20,  'selling_price'=>3.00,  'unit'=>'tablet'],
                ['name'=>'Budesonide Inhaler 200mcg',  'generic_name'=>'Budesonide',          'form'=>'MDI',     'category'=>'Pernafasan',       'classification'=>'general',    'lot_number'=>'BUD-2508', 'expiry_date'=>'2027-07-31', 'supplier'=>'AstraZeneca', 'stock_quantity'=>14,   'reorder_level'=>20,  'unit_cost'=>32.50, 'selling_price'=>48.00, 'unit'=>'inhaler'],
                ['name'=>'Cetirizine 10mg',            'generic_name'=>'Cetirizine HCl',      'form'=>'Tablet',  'category'=>'Antihistamin',     'classification'=>'general',    'lot_number'=>'CET-2510', 'expiry_date'=>'2028-05-31', 'supplier'=>'Duopharma',   'stock_quantity'=>320,  'reorder_level'=>100, 'unit_cost'=>0.15,  'selling_price'=>0.45,  'unit'=>'tablet'],
                ['name'=>'Morphine Sulphate 10mg',     'generic_name'=>'Morphine Sulphate',   'form'=>'Tablet',  'category'=>'Analgesik',        'classification'=>'controlled', 'lot_number'=>'MOR-2501', 'expiry_date'=>'2026-08-31', 'supplier'=>'Hospira',     'stock_quantity'=>30,   'reorder_level'=>20,  'unit_cost'=>4.50,  'selling_price'=>12.00, 'unit'=>'tablet', 'notes'=>'Stok kawalan — rekod wajib'],
                ['name'=>'Ibuprofen 400mg',            'generic_name'=>'Ibuprofen',           'form'=>'Tablet',  'category'=>'Analgesik',        'classification'=>'general',    'lot_number'=>'IBU-2506', 'expiry_date'=>'2027-05-31', 'supplier'=>'Duopharma',   'stock_quantity'=>480,  'reorder_level'=>200, 'unit_cost'=>0.25,  'selling_price'=>0.70,  'unit'=>'tablet'],
                ['name'=>'Montelukast 10mg',           'generic_name'=>'Montelukast Sodium',  'form'=>'Tablet',  'category'=>'Pernafasan',       'classification'=>'general',    'lot_number'=>'MON-2508', 'expiry_date'=>'2027-08-31', 'supplier'=>'Merck',       'stock_quantity'=>150,  'reorder_level'=>60,  'unit_cost'=>0.80,  'selling_price'=>2.20,  'unit'=>'tablet'],
                ['name'=>'Aspirin 75mg',               'generic_name'=>'Acetylsalicylic Acid','form'=>'Tablet',  'category'=>'Kardiologi',       'classification'=>'general',    'lot_number'=>'ASP-2501', 'expiry_date'=>'2027-03-31', 'supplier'=>'Bayer',       'stock_quantity'=>600,  'reorder_level'=>200, 'unit_cost'=>0.08,  'selling_price'=>0.25,  'unit'=>'tablet'],
                ['name'=>'Folic Acid 5mg',             'generic_name'=>'Folic Acid',          'form'=>'Tablet',  'category'=>'Vitamin',          'classification'=>'general',    'lot_number'=>'FOL-2503', 'expiry_date'=>'2028-02-28', 'supplier'=>'Pharmaniaga', 'stock_quantity'=>900,  'reorder_level'=>300, 'unit_cost'=>0.12,  'selling_price'=>0.35,  'unit'=>'tablet'],
                ['name'=>'Ferrous Fumarate 200mg',     'generic_name'=>'Ferrous Fumarate',    'form'=>'Tablet',  'category'=>'Vitamin',          'classification'=>'general',    'lot_number'=>'FER-2504', 'expiry_date'=>'2027-10-31', 'supplier'=>'Duopharma',   'stock_quantity'=>720,  'reorder_level'=>300, 'unit_cost'=>0.10,  'selling_price'=>0.30,  'unit'=>'tablet'],
            ];
            foreach ($inventory as $data) {
                $item = InventoryItem::create(array_merge($data, ['status' => 'active']));
                $item->transactions()->create([
                    'type' => 'in', 'quantity_delta' => $item->stock_quantity,
                    'quantity_after' => $item->stock_quantity, 'reference' => 'Stok awal', 'performed_by' => 'System',
                ]);
            }
        } else {
            InventoryItem::where('selling_price', 0)->orWhereNull('selling_price')->each(function ($item) {
                $item->update(['selling_price' => round($item->unit_cost * 2.5, 2)]);
            });
        }

        // ── Appointments ───────────────────────────────────────
        if (Appointment::count() === 0) {
            $docName = 'Dr. Aiman Rashid';
            $mon  = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $tue  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDay()->format('Y-m-d');
            $wed  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(2)->format('Y-m-d');
            $thu  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(3)->format('Y-m-d');
            $fri  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(4)->format('Y-m-d');
            $sat  = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(5)->format('Y-m-d');
            $pt   = [];
            foreach (['780229-08-5234','650412-14-8821','920815-10-7733','850101-14-5678','010322-08-1145','950707-03-9988','750314-08-1122','880905-10-4477'] as $ic) {
                $pt[] = Patient::where('ic_number', $ic)->first();
            }
            $appts = [
                ['date'=>$mon,'time'=>'08:00','patient'=>$pt[0],'type'=>'follow_up',      'status'=>'done',      'reason'=>'Kawalan tekanan darah',        'duration'=>30],
                ['date'=>$mon,'time'=>'08:30','patient'=>$pt[1],'type'=>'follow_up',      'status'=>'done',      'reason'=>'Kawalan kolesterol',            'duration'=>30],
                ['date'=>$mon,'time'=>'09:00','patient'=>$pt[2],'type'=>'new',            'status'=>'done',      'reason'=>'Sesak nafas berulang',          'duration'=>30],
                ['date'=>$mon,'time'=>'09:30','patient'=>$pt[3],'type'=>'annual_checkup', 'status'=>'cancelled', 'reason'=>'Pemeriksaan tahunan',           'duration'=>30],
                ['date'=>$mon,'time'=>'10:00','patient'=>$pt[4],'type'=>'new',            'status'=>'done',      'reason'=>'Demam dan sakit tekak',         'duration'=>15],
                ['date'=>$tue,'time'=>'08:00','patient'=>$pt[5],'type'=>'antenatal',      'status'=>'confirmed', 'reason'=>'Antenatal minggu ke-24',        'duration'=>30],
                ['date'=>$tue,'time'=>'08:30','patient'=>$pt[6],'type'=>'follow_up',      'status'=>'waiting',   'reason'=>'Kawalan hipertensi',            'duration'=>30],
                ['date'=>$tue,'time'=>'09:00','patient'=>$pt[7],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Tiroid susulan',                'duration'=>30],
                ['date'=>$tue,'time'=>'10:00','patient'=>$pt[0],'type'=>'procedure',      'status'=>'confirmed', 'reason'=>'Ambil darah HbA1c',             'duration'=>15],
                ['date'=>$wed,'time'=>'08:00','patient'=>$pt[2],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Semakan ubat asma',             'duration'=>30],
                ['date'=>$wed,'time'=>'09:00','patient'=>$pt[3],'type'=>'new',            'status'=>'confirmed', 'reason'=>'Sakit kepala kronik',           'duration'=>30],
                ['date'=>$wed,'time'=>'10:00','patient'=>$pt[1],'type'=>'teleconsult',    'status'=>'confirmed', 'reason'=>'Teleperubatan bulanan',         'duration'=>30],
                ['date'=>$wed,'time'=>'11:00','patient'=>$pt[4],'type'=>'follow_up',      'status'=>'waiting',   'reason'=>'Susulan selepas rawatan',       'duration'=>15],
                ['date'=>$thu,'time'=>'08:30','patient'=>$pt[5],'type'=>'antenatal',      'status'=>'confirmed', 'reason'=>'Antenatal — scan 20 minggu',   'duration'=>45],
                ['date'=>$thu,'time'=>'09:30','patient'=>$pt[6],'type'=>'annual_checkup', 'status'=>'confirmed', 'reason'=>'Pemeriksaan tahunan',           'duration'=>30],
                ['date'=>$thu,'time'=>'10:30','patient'=>$pt[7],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Kawalan tiroid',                'duration'=>30],
                ['date'=>$fri,'time'=>'08:00','patient'=>$pt[0],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Semakan ubat DM + HTN',         'duration'=>30],
                ['date'=>$fri,'time'=>'09:00','patient'=>$pt[2],'type'=>'procedure',      'status'=>'confirmed', 'reason'=>'Ujian fungsi paru-paru',        'duration'=>30],
                ['date'=>$fri,'time'=>'10:00','patient'=>$pt[3],'type'=>'new',            'status'=>'no_show',   'reason'=>'Ruam kulit berulang',           'duration'=>30],
                ['date'=>$sat,'time'=>'08:00','patient'=>$pt[1],'type'=>'follow_up',      'status'=>'confirmed', 'reason'=>'Tekanan darah tinggi',          'duration'=>30],
                ['date'=>$sat,'time'=>'08:30','patient'=>$pt[4],'type'=>'new',            'status'=>'confirmed', 'reason'=>'Batuk berlarutan',              'duration'=>30],
            ];
            foreach ($appts as $a) {
                if (! $a['patient']) continue;
                Appointment::create([
                    'patient_id' => $a['patient']->id, 'doctor_name' => $docName,
                    'appointment_date' => $a['date'], 'appointment_time' => $a['time'],
                    'duration_minutes' => $a['duration'], 'type' => $a['type'],
                    'reason' => $a['reason'], 'status' => $a['status'],
                ]);
            }
        }

        // ── Visits (with prescriptions + invoices — unified bill workflow) ──
        if (Visit::count() === 0) {
            $doc = 'Dr. Aiman Rashid';
            $userId = User::where('email', 'aiman@alhuda.my')->value('id') ?? 1;

            $pAminah  = Patient::where('ic_number', '780229-08-5234')->first();
            $pLim     = Patient::where('ic_number', '650412-14-8821')->first();
            $pTan     = Patient::where('ic_number', '920815-10-7733')->first();
            $pRajesh  = Patient::where('ic_number', '010322-08-1145')->first();
            $pNurul   = Patient::where('ic_number', '950707-03-9988')->first();

            // Inventory refs
            $invMetformin  = InventoryItem::where('name', 'Metformin 500mg')->first();
            $invAmlodipine = InventoryItem::where('name', 'Amlodipine 5mg')->first();
            $invAtorva     = InventoryItem::where('name', 'Atorvastatin 20mg')->first();
            $invSalbutamol = InventoryItem::where('name', 'Salbutamol Inhaler 100mcg')->first();
            $invBudesonide = InventoryItem::where('name', 'Budesonide Inhaler 200mcg')->first();
            $invMontelukast= InventoryItem::where('name', 'Montelukast 10mg')->first();
            $invAzithro    = InventoryItem::where('name', 'Azithromycin 500mg')->first();
            $invParacetamol= InventoryItem::where('name', 'Paracetamol 1g')->first();
            $invFolic      = InventoryItem::where('name', 'Folic Acid 5mg')->first();
            $invFerrous    = InventoryItem::where('name', 'Ferrous Fumarate 200mg')->first();

            // helper: create invoice item
            $mkItem = fn($invoice, string $type, string $desc, float $qty, float $price) =>
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'type'        => $type,
                    'description' => $desc,
                    'quantity'    => $qty,
                    'unit_price'  => $price,
                    'total_price' => round($qty * $price, 2),
                ]);

            // ──────────────────────────────────────────────────────
            // VISIT 1 — Aminah (HTN+T2DM), CLOSED 2 days ago
            // Full workflow: services + prescription → closed → dispensed → paid
            // ──────────────────────────────────────────────────────
            if ($pAminah) {
                $v1 = Visit::create([
                    'patient_id'      => $pAminah->id, 'user_id' => $userId, 'doctor_name' => $doc,
                    'visit_date'      => now()->subDays(2)->format('Y-m-d'),
                    'chief_complaint' => 'Kawalan tekanan darah dan gula darah bulanan',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit hadir untuk kawalan rutin. Menyatakan tekanan darah telah stabil minggu ini. Tiada pening kepala atau sesak nafas. Pematuhan ubat baik. Makan mengikut diet.",
                    'soap_o'          => "BP: 138/86 mmHg\nHR: 78 bpm\nTemp: 36.8°C\nSpO2: 98%\nBerat: 67.5 kg\nGula darah rawak: 8.2 mmol/L",
                    'soap_a'          => "1. Hipertensi Penting — terkawal sederhana\n2. Diabetes Mellitus Jenis 2 — kawalan sederhana (HbA1c 7.4% bulan lalu)",
                    'soap_p'          => "1. Teruskan Amlodipine 5mg OD\n2. Teruskan Metformin 500mg BD\n3. Semak semula 4 minggu",
                    'signed_at'       => now()->subDays(2)->setTime(10, 30),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v1->id,'bp_systolic'=>138,'bp_diastolic'=>86,'heart_rate'=>78,'temperature'=>36.8,'spo2'=>98,'weight'=>67.5,'height'=>158]);
                VisitDiagnosis::create(['visit_id'=>$v1->id,'icd_code'=>'I10',  'description'=>'Hipertensi penting','type'=>'primary']);
                VisitDiagnosis::create(['visit_id'=>$v1->id,'icd_code'=>'E11.9','description'=>'Diabetes Mellitus Jenis 2 tanpa komplikasi','type'=>'secondary']);

                // Prescription — dispensed (was draft, promoted, dispensed by pharmacy)
                $rx1 = Prescription::create([
                    'patient_id' => $pAminah->id, 'visit_id' => $v1->id, 'user_id' => $userId,
                    'prescribing_doctor' => $doc, 'status' => 'dispensed',
                    'drug_check_passed' => true, 'drug_check_notes' => 'Alahan Penicillin diambil kira. Tiada interaksi kritikal.',
                    'dispensed_at' => now()->subDays(2)->setTime(11, 0), 'dispensed_by' => 'Mr. Vinod',
                ]);
                $rx1->items()->create(['drug_name'=>'Metformin 500mg',  'inventory_item_id'=>$invMetformin?->id,  'drug_unit'=>'Tablet', 'kegunaan'=>'Kawalan gula darah',       'dosage'=>'500mg', 'frequency'=>'BD - 2x sehari', 'duration'=>'30 hari', 'quantity'=>60, 'instructions'=>'Selepas makan']);
                $rx1->items()->create(['drug_name'=>'Amlodipine 5mg',   'inventory_item_id'=>$invAmlodipine?->id, 'drug_unit'=>'Tablet', 'kegunaan'=>'Kawalan tekanan darah',    'dosage'=>'5mg',   'frequency'=>'OD - 1x sehari', 'duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Waktu pagi']);
                $rx1->items()->create(['drug_name'=>'Atorvastatin 20mg','inventory_item_id'=>$invAtorva?->id,     'drug_unit'=>'Tablet', 'kegunaan'=>'Kawalan kolesterol',       'dosage'=>'20mg',  'frequency'=>'ON - waktu malam','duration'=>'30 hari', 'quantity'=>30, 'instructions'=>'Sebelum tidur']);

                // Invoice — PAID (services + drugs, was emr_draft → promoted → dispensed added drugs → paid)
                $inv1 = Invoice::create([
                    'patient_id' => $pAminah->id, 'visit_id' => $v1->id,
                    'invoice_date' => now()->subDays(2)->format('Y-m-d'),
                    'status' => 'paid', 'payment_method' => 'cash',
                    'discount_amount' => 0,
                    'paid_at' => now()->subDays(2)->setTime(11, 15), 'paid_by' => 'Pn. Salina',
                ]);
                $mkItem($inv1, 'consultation', 'Yuran Perundingan — Dr. Aiman Rashid', 1, 35.00);
                $mkItem($inv1, 'procedure',    'Ambil Darah (FBC + HbA1c)',            1, 25.00);
                $mkItem($inv1, 'drug',         'Metformin 500mg × 60 tab',            60,  0.40);
                $mkItem($inv1, 'drug',         'Amlodipine 5mg × 30 tab',             30,  0.65);
                $mkItem($inv1, 'drug',         'Atorvastatin 20mg × 30 tab',          30,  1.20);
                $inv1->recalc();
            }

            // ──────────────────────────────────────────────────────
            // VISIT 2 — Tan Wei Ming (Asma), CLOSED 3 days ago
            // Full workflow: services + prescription → closed → dispensed → paid
            // ──────────────────────────────────────────────────────
            if ($pTan) {
                $v2 = Visit::create([
                    'patient_id'      => $pTan->id, 'user_id' => $userId, 'doctor_name' => $doc,
                    'visit_date'      => now()->subDays(3)->format('Y-m-d'),
                    'chief_complaint' => 'Sesak nafas berulang dan batuk malam',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit mengadu sesak nafas berulang terutama pada waktu malam dan pagi. Batuk kering sejak 1 minggu. Terdedah kepada asap rokok di tempat kerja. Menggunakan inhaler salbutamol 2-3 kali seminggu.",
                    'soap_o'          => "BP: 118/76 mmHg\nHR: 84 bpm\nTemp: 36.9°C\nSpO2: 96%\nBerat: 72 kg\nWheeze bilateral semasa auskultasi",
                    'soap_a'          => "Asma bronkial — kurang terkawal (GINA Step 2)",
                    'soap_p'          => "1. Mulakan Budesonide 200mcg inhaler BD\n2. Teruskan Salbutamol PRN\n3. Nasihat elak pencetus\n4. Semak semula 2 minggu",
                    'signed_at'       => now()->subDays(3)->setTime(9, 15),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v2->id,'bp_systolic'=>118,'bp_diastolic'=>76,'heart_rate'=>84,'temperature'=>36.9,'spo2'=>96,'weight'=>72.0,'height'=>172]);
                VisitDiagnosis::create(['visit_id'=>$v2->id,'icd_code'=>'J45.9','description'=>'Asma bronkial, tidak spesifik','type'=>'primary']);

                $rx2 = Prescription::create([
                    'patient_id' => $pTan->id, 'visit_id' => $v2->id, 'user_id' => $userId,
                    'prescribing_doctor' => $doc, 'status' => 'dispensed',
                    'drug_check_passed' => true, 'drug_check_notes' => 'Alahan Aspirin diambil kira. Tiada NSAID. Selamat.',
                    'dispensed_at' => now()->subDays(3)->setTime(9, 45), 'dispensed_by' => 'Mr. Vinod',
                ]);
                $rx2->items()->create(['drug_name'=>'Salbutamol Inhaler 100mcg', 'inventory_item_id'=>$invSalbutamol?->id, 'drug_unit'=>'MDI',    'kegunaan'=>'Melegakan sesak nafas', 'dosage'=>'2 puff', 'frequency'=>'PRN - bila perlu', 'duration'=>'—',      'quantity'=>1, 'instructions'=>'Bila sesak nafas', 'is_prn'=>true]);
                $rx2->items()->create(['drug_name'=>'Budesonide Inhaler 200mcg', 'inventory_item_id'=>$invBudesonide?->id, 'drug_unit'=>'MDI',    'kegunaan'=>'Rawatan asma',          'dosage'=>'1 puff', 'frequency'=>'BD - 2x sehari',   'duration'=>'30 hari', 'quantity'=>1, 'instructions'=>'Pagi & malam',   'complete_course'=>true]);
                $rx2->items()->create(['drug_name'=>'Montelukast 10mg',          'inventory_item_id'=>$invMontelukast?->id,'drug_unit'=>'Tablet', 'kegunaan'=>'Kawalan asma',          'dosage'=>'10mg',   'frequency'=>'ON - waktu malam',  'duration'=>'30 hari', 'quantity'=>30,'instructions'=>'Sebelum tidur',  'complete_course'=>true]);

                $inv2 = Invoice::create([
                    'patient_id' => $pTan->id, 'visit_id' => $v2->id,
                    'invoice_date' => now()->subDays(3)->format('Y-m-d'),
                    'status' => 'paid', 'payment_method' => 'card',
                    'discount_amount' => 0,
                    'paid_at' => now()->subDays(3)->setTime(10, 0), 'paid_by' => 'Pn. Salina',
                ]);
                $mkItem($inv2, 'consultation', 'Yuran Perundingan — Dr. Aiman Rashid',  1,  35.00);
                $mkItem($inv2, 'procedure',    'Spirometry (Ujian Fungsi Paru-paru)',    1,  25.00);
                $mkItem($inv2, 'drug',         'Salbutamol Inhaler 100mcg',             1,  28.00);
                $mkItem($inv2, 'drug',         'Budesonide Inhaler 200mcg',             1,  48.00);
                $mkItem($inv2, 'drug',         'Montelukast 10mg × 30 tab',            30,   2.20);
                $inv2->recalc();
            }

            // ──────────────────────────────────────────────────────
            // VISIT 3 — Lim Kok Wing (Angina), OPEN today
            // Doctor added services; prescription not yet added; EMR not closed
            // ──────────────────────────────────────────────────────
            if ($pLim) {
                $v3 = Visit::create([
                    'patient_id'      => $pLim->id, 'user_id' => $userId, 'doctor_name' => $doc,
                    'visit_date'      => now()->format('Y-m-d'),
                    'chief_complaint' => 'Sakit dada dan berdebar-debar',
                    'status'          => 'open',
                    'soap_s'          => "Pesakit hadir dengan aduan sakit dada tekanan sejak 2 jam. Sakit menjalar ke lengan kiri. Berdebar-debar. Ada riwayat CAD dan dyslipidemia.",
                    'soap_o'          => "BP: 156/94 mmHg\nHR: 96 bpm\nTemp: 37.1°C\nSpO2: 97%\nBerat: 78 kg\nECG: ST depression V4-V6",
                    'soap_a'          => "Angina tidak stabil — perlu penilaian kardiologi segera",
                    'soap_p'          => null,
                ]);
                VisitVital::create(['visit_id'=>$v3->id,'bp_systolic'=>156,'bp_diastolic'=>94,'heart_rate'=>96,'temperature'=>37.1,'spo2'=>97,'weight'=>78.0,'height'=>168]);
                VisitDiagnosis::create(['visit_id'=>$v3->id,'icd_code'=>'I20.0','description'=>'Angina tidak stabil','type'=>'primary']);
                VisitDiagnosis::create(['visit_id'=>$v3->id,'icd_code'=>'E78.5','description'=>'Hiperlipidemia campuran','type'=>'secondary']);

                // emr_draft invoice — services added by doctor, not yet sent to billing
                $inv3 = Invoice::create([
                    'patient_id' => $pLim->id, 'visit_id' => $v3->id,
                    'invoice_date' => now()->format('Y-m-d'), 'status' => 'emr_draft',
                ]);
                $mkItem($inv3, 'consultation', 'Yuran Perundingan — Dr. Aiman Rashid', 1, 35.00);
                $mkItem($inv3, 'procedure',    'Elektrokardiogram (ECG)',              1, 25.00);
                $mkItem($inv3, 'lab',          'Troponin I Rapid Test',                1, 45.00);
                $inv3->recalc();
            }

            // ──────────────────────────────────────────────────────
            // VISIT 4 — Rajesh Kumar (Faringitis), OPEN today
            // Doctor added services + draft prescription; EMR not closed
            // ──────────────────────────────────────────────────────
            if ($pRajesh) {
                $v4 = Visit::create([
                    'patient_id'      => $pRajesh->id, 'user_id' => $userId, 'doctor_name' => $doc,
                    'visit_date'      => now()->format('Y-m-d'),
                    'chief_complaint' => 'Demam tinggi dan sakit tekak 3 hari',
                    'status'          => 'open',
                    'soap_s'          => "Pesakit berusia 25 tahun hadir dengan demam tinggi maksimum 39.2°C, sakit tekak teruk, dan susah menelan sejak 3 hari. Tiada batuk. Alahan Sulfa diketahui.",
                    'soap_o'          => "BP: 110/70 mmHg\nHR: 102 bpm\nTemp: 38.8°C\nSpO2: 99%\nBerat: 68 kg\nTonsil: exudate bilateral, cervical lymphadenopathy",
                    'soap_a'          => 'Faringitis akut — kemungkinan Group A Streptococcus',
                    'soap_p'          => "1. Azithromycin 500mg OD × 3 hari (Sulfa alahan — guna makrolid)\n2. Paracetamol 1g TDS PRN demam\n3. Rehat dan banyak minum air",
                ]);
                VisitVital::create(['visit_id'=>$v4->id,'bp_systolic'=>110,'bp_diastolic'=>70,'heart_rate'=>102,'temperature'=>38.8,'spo2'=>99,'weight'=>68.0,'height'=>175]);
                VisitDiagnosis::create(['visit_id'=>$v4->id,'icd_code'=>'J02.0','description'=>'Faringitis streptokokal','type'=>'primary']);

                // Draft prescription — in EMR, not yet sent to pharmacy
                $rx4 = Prescription::create([
                    'patient_id' => $pRajesh->id, 'visit_id' => $v4->id, 'user_id' => $userId,
                    'prescribing_doctor' => $doc, 'status' => 'draft',
                    'drug_check_passed' => true,
                    'drug_check_notes' => 'Alahan Sulfa disahkan. Azithromycin (makrolid) selamat.',
                ]);
                $rx4->items()->create(['drug_name'=>'Azithromycin 500mg','inventory_item_id'=>$invAzithro?->id,    'drug_unit'=>'Tablet','kegunaan'=>'Rawatan jangkitan bakteria','dosage'=>'500mg','frequency'=>'OD - 1x sehari','duration'=>'3 hari', 'quantity'=>3,  'instructions'=>'Selepas makan',  'complete_course'=>true]);
                $rx4->items()->create(['drug_name'=>'Paracetamol 1g',    'inventory_item_id'=>$invParacetamol?->id,'drug_unit'=>'Tablet','kegunaan'=>'Turunkan demam dan sakit',  'dosage'=>'1g',  'frequency'=>'TDS - 3x sehari', 'duration'=>'3 hari', 'quantity'=>9,  'instructions'=>'Bila demam/sakit','is_prn'=>true]);

                // emr_draft invoice — services + pending drugs
                $inv4 = Invoice::create([
                    'patient_id' => $pRajesh->id, 'visit_id' => $v4->id,
                    'invoice_date' => now()->format('Y-m-d'), 'status' => 'emr_draft',
                ]);
                $mkItem($inv4, 'consultation', 'Yuran Perundingan — Dr. Aiman Rashid', 1, 35.00);
                $mkItem($inv4, 'lab',          'Rapid Strep Test (Streptococcus A)',   1, 20.00);
                $inv4->recalc();
            }

            // ──────────────────────────────────────────────────────
            // VISIT 5 — Nurul Ain (Antenatal), CLOSED 7 days ago
            // No prescription; services only; invoice paid
            // ──────────────────────────────────────────────────────
            if ($pNurul) {
                $v5 = Visit::create([
                    'patient_id'      => $pNurul->id, 'user_id' => $userId, 'doctor_name' => $doc,
                    'visit_date'      => now()->subDays(7)->format('Y-m-d'),
                    'chief_complaint' => 'Pemeriksaan antenatal mingguan ke-24',
                    'status'          => 'closed',
                    'soap_s'          => "Pesakit G1P0, 24 minggu mengandung. Rasa pergerakan janin aktif. Tiada pendarahan per vaginam. Mual pagi berkurang. Pematuhan folate dan iron baik.",
                    'soap_o'          => "BP: 112/72 mmHg\nHR: 82 bpm\nTemp: 36.7°C\nSpO2: 99%\nBerat: 58 kg (naik 1.2kg)\nFH: 24 cm, FHR: 148 bpm",
                    'soap_a'          => "Kehamilan normal 24 minggu. Perkembangan janin baik.",
                    'soap_p'          => "1. Teruskan Folic Acid 5mg OD\n2. Teruskan Ferrous Fumarate 200mg BD\n3. OGTT dijadualkan minggu 28\n4. Semak semula 4 minggu",
                    'signed_at'       => now()->subDays(7)->setTime(11, 0),
                    'signed_by'       => $doc,
                ]);
                VisitVital::create(['visit_id'=>$v5->id,'bp_systolic'=>112,'bp_diastolic'=>72,'heart_rate'=>82,'temperature'=>36.7,'spo2'=>99,'weight'=>58.0,'height'=>160]);
                VisitDiagnosis::create(['visit_id'=>$v5->id,'icd_code'=>'Z34.2','description'=>'Pengawasan kehamilan normal trimester kedua','type'=>'primary']);

                $rx5 = Prescription::create([
                    'patient_id' => $pNurul->id, 'visit_id' => $v5->id, 'user_id' => $userId,
                    'prescribing_doctor' => $doc, 'status' => 'dispensed',
                    'drug_check_passed' => true, 'drug_check_notes' => 'Selamat untuk kehamilan.',
                    'dispensed_at' => now()->subDays(7)->setTime(11, 30), 'dispensed_by' => 'Mr. Vinod',
                ]);
                $rx5->items()->create(['drug_name'=>'Folic Acid 5mg',        'inventory_item_id'=>$invFolic?->id,  'drug_unit'=>'Tablet','kegunaan'=>'Suplemen kehamilan','dosage'=>'5mg',   'frequency'=>'OD','duration'=>'30 hari','quantity'=>30,'instructions'=>'Selepas makan','complete_course'=>true]);
                $rx5->items()->create(['drug_name'=>'Ferrous Fumarate 200mg','inventory_item_id'=>$invFerrous?->id,'drug_unit'=>'Tablet','kegunaan'=>'Suplemen zat besi', 'dosage'=>'200mg','frequency'=>'BD','duration'=>'30 hari','quantity'=>60,'instructions'=>'Selepas makan','complete_course'=>true]);

                $inv5 = Invoice::create([
                    'patient_id' => $pNurul->id, 'visit_id' => $v5->id,
                    'invoice_date' => now()->subDays(7)->format('Y-m-d'),
                    'status' => 'paid', 'payment_method' => 'duitnow',
                    'discount_amount' => 0,
                    'paid_at' => now()->subDays(7)->setTime(11, 45), 'paid_by' => 'Pn. Salina',
                ]);
                $mkItem($inv5, 'consultation', 'Lawatan Antenatal — Dr. Aiman Rashid', 1, 45.00);
                $mkItem($inv5, 'procedure',    'Ultrasound Antenatal',                  1, 50.00);
                $mkItem($inv5, 'drug',         'Folic Acid 5mg × 30 tab',             30,  0.35);
                $mkItem($inv5, 'drug',         'Ferrous Fumarate 200mg × 60 tab',      60,  0.30);
                $inv5->recalc();
            }
        }

        // ── Standalone pharmacy queue (no visit — walk-in or old workflow) ──
        if (Prescription::whereNull('visit_id')->count() === 0) {
            $pHassan = Patient::where('ic_number', '750314-08-1122')->first();
            $pWong   = Patient::where('ic_number', '880905-10-4477')->first();
            $userId  = User::where('email', 'aiman@alhuda.my')->value('id') ?? 1;

            if ($pHassan) {
                $rxH = Prescription::create([
                    'patient_id' => $pHassan->id, 'user_id' => $userId,
                    'prescribing_doctor' => 'Dr. Aiman Rashid', 'status' => 'pending',
                    'drug_check_passed' => true, 'drug_check_notes' => 'Tiada interaksi kritikal.',
                ]);
                $rxH->items()->create(['drug_name'=>'Amlodipine 5mg',   'drug_unit'=>'Tablet','kegunaan'=>'Kawalan tekanan darah','dosage'=>'5mg', 'frequency'=>'OD','duration'=>'30 hari','quantity'=>30,'instructions'=>'Waktu pagi']);
                $rxH->items()->create(['drug_name'=>'Losartan 50mg',    'drug_unit'=>'Tablet','kegunaan'=>'Kawalan tekanan darah','dosage'=>'50mg','frequency'=>'OD','duration'=>'30 hari','quantity'=>30,'instructions'=>'Selepas sarapan']);
                $rxH->items()->create(['drug_name'=>'Atorvastatin 20mg','drug_unit'=>'Tablet','kegunaan'=>'Kawalan kolesterol',   'dosage'=>'20mg','frequency'=>'ON','duration'=>'30 hari','quantity'=>30,'instructions'=>'Sebelum tidur']);
            }

            if ($pWong) {
                $rxW = Prescription::create([
                    'patient_id' => $pWong->id, 'user_id' => $userId,
                    'prescribing_doctor' => 'Dr. Aiman Rashid', 'status' => 'verifying',
                    'drug_check_passed' => false, 'drug_check_notes' => 'Semak dos Levothyroxine — berat badan berubah.',
                ]);
                $rxW->items()->create(['drug_name'=>'Levothyroxine 50mcg','drug_unit'=>'Tablet','kegunaan'=>'Kawalan tiroid','dosage'=>'50mcg','frequency'=>'OD','duration'=>'30 hari','quantity'=>30,'instructions'=>'30 minit sebelum sarapan']);
            }
        }

        // ── Historical standalone invoices (no visit) ──────────
        if (Invoice::whereNull('visit_id')->count() === 0) {
            $pAminah = Patient::where('ic_number', '780229-08-5234')->first();
            $pHassan = Patient::where('ic_number', '750314-08-1122')->first();

            if ($pAminah) {
                $invOld1 = Invoice::create([
                    'patient_id' => $pAminah->id, 'invoice_date' => now()->subDays(30)->format('Y-m-d'),
                    'status' => 'paid', 'payment_method' => 'cash', 'discount_amount' => 0,
                    'paid_at' => now()->subDays(30)->setTime(9, 0), 'paid_by' => 'Pn. Salina',
                ]);
                InvoiceItem::create(['invoice_id'=>$invOld1->id,'type'=>'consultation','description'=>'Rawatan GP — Dr. Aiman Rashid','quantity'=>1, 'unit_price'=>35.00,'total_price'=>35.00]);
                InvoiceItem::create(['invoice_id'=>$invOld1->id,'type'=>'drug',        'description'=>'Metformin 500mg × 60 tab',   'quantity'=>60,'unit_price'=>0.40, 'total_price'=>24.00]);
                InvoiceItem::create(['invoice_id'=>$invOld1->id,'type'=>'drug',        'description'=>'Amlodipine 5mg × 30 tab',    'quantity'=>30,'unit_price'=>0.65, 'total_price'=>19.50]);
                $invOld1->recalc();
            }

            if ($pHassan) {
                $invOld2 = Invoice::create([
                    'patient_id' => $pHassan->id, 'invoice_date' => now()->subDays(14)->format('Y-m-d'),
                    'status' => 'unpaid', 'payment_method' => 'panel', 'discount_amount' => 0,
                ]);
                InvoiceItem::create(['invoice_id'=>$invOld2->id,'type'=>'consultation','description'=>'Rawatan GP — Dr. Aiman Rashid', 'quantity'=>1, 'unit_price'=>35.00,'total_price'=>35.00]);
                InvoiceItem::create(['invoice_id'=>$invOld2->id,'type'=>'drug',        'description'=>'Amlodipine 5mg × 30 tab',     'quantity'=>30,'unit_price'=>0.65, 'total_price'=>19.50]);
                InvoiceItem::create(['invoice_id'=>$invOld2->id,'type'=>'drug',        'description'=>'Losartan 50mg × 30 tab',      'quantity'=>30,'unit_price'=>0.90, 'total_price'=>27.00]);
                $invOld2->recalc();
            }
        }

        // ── Link prescription items → inventory (belt-and-suspenders) ──
        \App\Models\PrescriptionItem::whereNull('inventory_item_id')->each(function ($pi) {
            $inv = InventoryItem::whereRaw('LOWER(name) = ?', [strtolower($pi->drug_name)])->first()
                ?? InventoryItem::whereRaw('LOWER(generic_name) = ?', [strtolower($pi->drug_name)])->first();
            if ($inv) $pi->update(['inventory_item_id' => $inv->id]);
        });

        // ── Clinic profile ─────────────────────────────────────
        ClinicProfile::updateOrCreate(['id' => 1], [
            'name'         => 'Poliklinik Al-Huda',
            'tagline'      => 'Klinik Perubatan Berdaftar',
            'reg_number'   => 'KKLIU 1234/2025',
            'ckaps_number' => 'CKAPS-12345-P',
            'address'      => 'No. 12, Jalan Cempaka, Taman Harmoni',
            'postcode'     => '43000', 'city' => 'Kajang', 'state' => 'Selangor',
            'phone'        => '03-8888 1234', 'fax' => '03-8888 1235',
            'email'        => 'admin@poliklinikalhuda.com',
            'website'      => 'www.poliklinikalhuda.com',
        ]);

        // ── Medical Certificates ───────────────────────────────
        if (MedicalCertificate::count() === 0) {
            $doc  = 'Dr. Aiman Rashid';
            $pA   = Patient::where('ic_number', '780229-08-5234')->first();
            $pT   = Patient::where('ic_number', '920815-10-7733')->first();
            $pR   = Patient::where('ic_number', '010322-08-1145')->first();
            $vA   = $pA ? Visit::where('patient_id', $pA->id)->latest()->first() : null;
            $vT   = $pT ? Visit::where('patient_id', $pT->id)->latest()->first() : null;
            $vR   = $pR ? Visit::where('patient_id', $pR->id)->latest()->first() : null;

            if ($pA) MedicalCertificate::create(['patient_id'=>$pA->id,'visit_id'=>$vA?->id,'issued_by'=>$doc,'issue_date'=>now()->subDays(2)->format('Y-m-d'),'start_date'=>now()->subDays(2)->format('Y-m-d'),'end_date'=>now()->subDays(1)->format('Y-m-d'),'days'=>2,'diagnosis'=>'Hipertensi Penting; Diabetes Mellitus Jenis 2','notes'=>'Pesakit perlu berehat dan memantau tekanan darah di rumah.']);
            if ($pT) MedicalCertificate::create(['patient_id'=>$pT->id,'visit_id'=>$vT?->id,'issued_by'=>$doc,'issue_date'=>now()->subDays(3)->format('Y-m-d'),'start_date'=>now()->subDays(3)->format('Y-m-d'),'end_date'=>now()->subDays(1)->format('Y-m-d'),'days'=>3,'diagnosis'=>'Asma Bronkial — tidak terkawal','notes'=>'Elak pendedahan kepada asap dan habuk.']);
            if ($pR) MedicalCertificate::create(['patient_id'=>$pR->id,'visit_id'=>$vR?->id,'issued_by'=>$doc,'issue_date'=>now()->format('Y-m-d'),'start_date'=>now()->format('Y-m-d'),'end_date'=>now()->addDays(2)->format('Y-m-d'),'days'=>3,'diagnosis'=>'Faringitis Streptokokal Akut','notes'=>'Pesakit perlu berehat sepenuhnya dan habiskan kos antibiotik.']);
        }

        // ── Referral Letters ───────────────────────────────────
        if (ReferralLetter::count() === 0) {
            $doc = 'Dr. Aiman Rashid';
            $pL  = Patient::where('ic_number', '650412-14-8821')->first();
            $pT  = Patient::where('ic_number', '920815-10-7733')->first();
            $vL  = $pL ? Visit::where('patient_id', $pL->id)->latest()->first() : null;
            $vT  = $pT ? Visit::where('patient_id', $pT->id)->latest()->first() : null;

            if ($pL) ReferralLetter::create(['patient_id'=>$pL->id,'visit_id'=>$vL?->id,'issued_by'=>$doc,'issue_date'=>now()->format('Y-m-d'),'referred_to'=>'Hospital Universiti Kebangsaan Malaysia (HUKM)','referred_to_dept'=>'Jabatan Kardiologi','urgency'=>'urgent','reason'=>'Sakit dada tekanan dengan menjalar ke lengan kiri. ST depression V4-V6 pada ECG. Riwayat CAD.','clinical_summary'=>"Pesakit lelaki, 60 tahun, riwayat CAD dan dyslipidemia. ECG menunjukkan ST depression di V4-V6. BP: 156/94 mmHg, HR: 96 bpm.",'relevant_history'=>"HTN, CAD, Dyslipidemia. Ubat semasa: Aspirin 75mg OD, Rosuvastatin 10mg ON."]);
            if ($pT) ReferralLetter::create(['patient_id'=>$pT->id,'visit_id'=>$vT?->id,'issued_by'=>$doc,'issue_date'=>now()->subDays(3)->format('Y-m-d'),'referred_to'=>'Hospital Kajang','referred_to_dept'=>'Jabatan Pulmologi','urgency'=>'routine','reason'=>'Asma bronkial kurang terkawal. Memerlukan penilaian lanjut dan ujian fungsi paru-paru terperinci.','clinical_summary'=>"Pesakit lelaki, 33 tahun. SpO2: 96%, wheeze bilateral. Spirometry menunjukkan pola obstruktif.",'relevant_history'=>"Asma sejak usia 10 tahun. Terdedah kepada asap rokok di tempat kerja."]);
        }

        // ── Quarantine Letters ─────────────────────────────────
        if (QuarantineLetter::count() === 0) {
            $doc = 'Dr. Aiman Rashid';
            $pR  = Patient::where('ic_number', '010322-08-1145')->first();
            $pT  = Patient::where('ic_number', '920815-10-7733')->first();
            $vR  = $pR ? Visit::where('patient_id', $pR->id)->latest()->first() : null;

            if ($pR) QuarantineLetter::create(['patient_id'=>$pR->id,'visit_id'=>$vR?->id,'issued_by'=>$doc,'issue_date'=>now()->format('Y-m-d'),'quarantine_start'=>now()->format('Y-m-d'),'quarantine_end'=>now()->addDays(4)->format('Y-m-d'),'days'=>5,'diagnosis'=>'Faringitis Streptokokal Akut','reason'=>'Penyakit berjangkit — risiko penularan. Pesakit perlu diasingkan sehingga simptom hilang.','notes'=>'Elak perhimpunan awam. Pakai pelitup muka jika perlu keluar.']);
            if ($pT) QuarantineLetter::create(['patient_id'=>$pT->id,'visit_id'=>null,'issued_by'=>$doc,'issue_date'=>now()->subDays(10)->format('Y-m-d'),'quarantine_start'=>now()->subDays(10)->format('Y-m-d'),'quarantine_end'=>now()->subDays(5)->format('Y-m-d'),'days'=>5,'diagnosis'=>'Influenza A','reason'=>'Penyakit berjangkit. Karantina di rumah untuk mencegah penularan.','notes'=>null]);
        }

        // ── Time Slips ─────────────────────────────────────────
        if (TimeSlip::count() === 0) {
            $doc = 'Dr. Aiman Rashid';
            $pA  = Patient::where('ic_number', '780229-08-5234')->first();
            $pR  = Patient::where('ic_number', '010322-08-1145')->first();
            $pN  = Patient::where('ic_number', '950707-03-9988')->first();
            $vA  = $pA ? Visit::where('patient_id', $pA->id)->latest()->first() : null;
            $vR  = $pR ? Visit::where('patient_id', $pR->id)->latest()->first() : null;
            $vN  = $pN ? Visit::where('patient_id', $pN->id)->latest()->first() : null;

            if ($pA) TimeSlip::create(['patient_id'=>$pA->id,'visit_id'=>$vA?->id,'issued_by'=>$doc,'slip_date'=>now()->subDays(2)->format('Y-m-d'),'arrival_time'=>'09:00','departure_time'=>'10:30','purpose'=>'Kawalan darah tinggi dan kencing manis — pengambilan darah & rawatan','notes'=>'Pesakit memerlukan slip masa untuk majikan.']);
            if ($pR) TimeSlip::create(['patient_id'=>$pR->id,'visit_id'=>$vR?->id,'issued_by'=>$doc,'slip_date'=>now()->format('Y-m-d'),'arrival_time'=>'08:30','departure_time'=>'10:00','purpose'=>'Rawatan faringitis akut — pemeriksaan dan ubat-ubatan','notes'=>null]);
            if ($pN) TimeSlip::create(['patient_id'=>$pN->id,'visit_id'=>$vN?->id,'issued_by'=>$doc,'slip_date'=>now()->subDays(7)->format('Y-m-d'),'arrival_time'=>'10:00','departure_time'=>'11:30','purpose'=>'Pemeriksaan antenatal minggu ke-24 — scan dan ujian darah','notes'=>'Pesakit mengandung 24 minggu.']);
        }
    }
}
