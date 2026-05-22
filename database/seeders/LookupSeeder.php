<?php

namespace Database\Seeders;

use App\Models\LookupCategory;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ── PESAKIT ─────────────────────────────────────────────────────
            [
                'group' => 'patient', 'slug' => 'jantina', 'sort_order' => 1,
                'name_ms' => 'Jantina', 'name_en' => 'Gender',
                'description_ms' => 'Jantina pesakit', 'description_en' => 'Patient gender',
                'values' => [
                    ['code' => 'male',   'label_ms' => 'Lelaki',     'label_en' => 'Male',   'sort_order' => 1, 'is_system' => true],
                    ['code' => 'female', 'label_ms' => 'Perempuan',  'label_en' => 'Female', 'sort_order' => 2, 'is_system' => true],
                ],
            ],
            [
                'group' => 'patient', 'slug' => 'kumpulan_darah', 'sort_order' => 2,
                'name_ms' => 'Kumpulan Darah', 'name_en' => 'Blood Type',
                'description_ms' => 'Kumpulan darah pesakit', 'description_en' => 'Patient blood type',
                'values' => [
                    ['code' => 'A+',      'label_ms' => 'A Positif',   'label_en' => 'A Positive',   'sort_order' => 1, 'is_system' => true],
                    ['code' => 'A-',      'label_ms' => 'A Negatif',   'label_en' => 'A Negative',   'sort_order' => 2, 'is_system' => true],
                    ['code' => 'B+',      'label_ms' => 'B Positif',   'label_en' => 'B Positive',   'sort_order' => 3, 'is_system' => true],
                    ['code' => 'B-',      'label_ms' => 'B Negatif',   'label_en' => 'B Negative',   'sort_order' => 4, 'is_system' => true],
                    ['code' => 'O+',      'label_ms' => 'O Positif',   'label_en' => 'O Positive',   'sort_order' => 5, 'is_system' => true],
                    ['code' => 'O-',      'label_ms' => 'O Negatif',   'label_en' => 'O Negative',   'sort_order' => 6, 'is_system' => true],
                    ['code' => 'AB+',     'label_ms' => 'AB Positif',  'label_en' => 'AB Positive',  'sort_order' => 7, 'is_system' => true],
                    ['code' => 'AB-',     'label_ms' => 'AB Negatif',  'label_en' => 'AB Negative',  'sort_order' => 8, 'is_system' => true],
                    ['code' => 'Unknown', 'label_ms' => 'Tidak Diketahui', 'label_en' => 'Unknown',  'sort_order' => 9, 'is_system' => true],
                ],
            ],
            [
                'group' => 'patient', 'slug' => 'negeri', 'sort_order' => 3,
                'name_ms' => 'Negeri', 'name_en' => 'State',
                'description_ms' => 'Negeri di Malaysia', 'description_en' => 'Malaysian states',
                'values' => [
                    ['code' => 'Johor',              'label_ms' => 'Johor',              'label_en' => 'Johor',              'sort_order' => 1,  'is_system' => true],
                    ['code' => 'Kedah',              'label_ms' => 'Kedah',              'label_en' => 'Kedah',              'sort_order' => 2,  'is_system' => true],
                    ['code' => 'Kelantan',           'label_ms' => 'Kelantan',           'label_en' => 'Kelantan',           'sort_order' => 3,  'is_system' => true],
                    ['code' => 'Melaka',             'label_ms' => 'Melaka',             'label_en' => 'Melaka',             'sort_order' => 4,  'is_system' => true],
                    ['code' => 'Negeri Sembilan',    'label_ms' => 'Negeri Sembilan',    'label_en' => 'Negeri Sembilan',    'sort_order' => 5,  'is_system' => true],
                    ['code' => 'Pahang',             'label_ms' => 'Pahang',             'label_en' => 'Pahang',             'sort_order' => 6,  'is_system' => true],
                    ['code' => 'Perak',              'label_ms' => 'Perak',              'label_en' => 'Perak',              'sort_order' => 7,  'is_system' => true],
                    ['code' => 'Perlis',             'label_ms' => 'Perlis',             'label_en' => 'Perlis',             'sort_order' => 8,  'is_system' => true],
                    ['code' => 'Pulau Pinang',       'label_ms' => 'Pulau Pinang',       'label_en' => 'Penang',             'sort_order' => 9,  'is_system' => true],
                    ['code' => 'Sabah',              'label_ms' => 'Sabah',              'label_en' => 'Sabah',              'sort_order' => 10, 'is_system' => true],
                    ['code' => 'Sarawak',            'label_ms' => 'Sarawak',            'label_en' => 'Sarawak',            'sort_order' => 11, 'is_system' => true],
                    ['code' => 'Selangor',           'label_ms' => 'Selangor',           'label_en' => 'Selangor',           'sort_order' => 12, 'is_system' => true],
                    ['code' => 'Terengganu',         'label_ms' => 'Terengganu',         'label_en' => 'Terengganu',         'sort_order' => 13, 'is_system' => true],
                    ['code' => 'W.P. Kuala Lumpur',  'label_ms' => 'W.P. Kuala Lumpur',  'label_en' => 'W.P. Kuala Lumpur',  'sort_order' => 14, 'is_system' => true],
                    ['code' => 'W.P. Labuan',        'label_ms' => 'W.P. Labuan',        'label_en' => 'W.P. Labuan',        'sort_order' => 15, 'is_system' => true],
                    ['code' => 'W.P. Putrajaya',     'label_ms' => 'W.P. Putrajaya',     'label_en' => 'W.P. Putrajaya',     'sort_order' => 16, 'is_system' => true],
                ],
            ],
            [
                'group' => 'patient', 'slug' => 'bangsa', 'sort_order' => 4,
                'name_ms' => 'Bangsa', 'name_en' => 'Race / Ethnicity',
                'description_ms' => 'Bangsa / etnik pesakit', 'description_en' => 'Patient race or ethnicity',
                'values' => [
                    ['code' => 'melayu',              'label_ms' => 'Melayu',                    'label_en' => 'Malay',                     'sort_order' => 1,  'is_system' => true],
                    ['code' => 'cina',                'label_ms' => 'Cina',                      'label_en' => 'Chinese',                   'sort_order' => 2,  'is_system' => true],
                    ['code' => 'india',               'label_ms' => 'India',                     'label_en' => 'Indian',                    'sort_order' => 3,  'is_system' => true],
                    ['code' => 'iban',                'label_ms' => 'Iban',                      'label_en' => 'Iban',                      'sort_order' => 4,  'is_system' => true],
                    ['code' => 'kadazan_dusun',       'label_ms' => 'Kadazan-Dusun',             'label_en' => 'Kadazan-Dusun',             'sort_order' => 5,  'is_system' => true],
                    ['code' => 'bidayuh',             'label_ms' => 'Bidayuh',                   'label_en' => 'Bidayuh',                   'sort_order' => 6,  'is_system' => true],
                    ['code' => 'bajau',               'label_ms' => 'Bajau',                     'label_en' => 'Bajau',                     'sort_order' => 7,  'is_system' => true],
                    ['code' => 'melanau',             'label_ms' => 'Melanau',                   'label_en' => 'Melanau',                   'sort_order' => 8,  'is_system' => true],
                    ['code' => 'murut',               'label_ms' => 'Murut',                     'label_en' => 'Murut',                     'sort_order' => 9,  'is_system' => true],
                    ['code' => 'orang_asli',          'label_ms' => 'Orang Asli',                'label_en' => 'Orang Asli',                'sort_order' => 10, 'is_system' => true],
                    ['code' => 'lain_bumiputera',     'label_ms' => 'Lain-lain Bumiputera',      'label_en' => 'Other Bumiputera',          'sort_order' => 11, 'is_system' => true],
                    ['code' => 'lain',                'label_ms' => 'Lain-lain',                 'label_en' => 'Others',                    'sort_order' => 12, 'is_system' => true],
                    ['code' => 'warga_asing',         'label_ms' => 'Warga Asing',               'label_en' => 'Foreigner',                 'sort_order' => 13, 'is_system' => true],
                ],
            ],
            [
                'group' => 'patient', 'slug' => 'penyakit_kronik', 'sort_order' => 5,
                'name_ms' => 'Penyakit Kronik', 'name_en' => 'Chronic Conditions',
                'description_ms' => 'Penyakit kronik pesakit', 'description_en' => 'Patient chronic conditions',
                'values' => [
                    ['code' => 'HTN',            'label_ms' => 'Hipertensi (HTN)',              'label_en' => 'Hypertension (HTN)',             'sort_order' => 1,  'is_system' => true],
                    ['code' => 'T2DM',           'label_ms' => 'Diabetes Mellitus Jenis 2',     'label_en' => 'Type 2 Diabetes Mellitus',       'sort_order' => 2,  'is_system' => true],
                    ['code' => 'CAD',            'label_ms' => 'Penyakit Jantung Koronari',     'label_en' => 'Coronary Artery Disease',        'sort_order' => 3,  'is_system' => true],
                    ['code' => 'Dyslipidemia',   'label_ms' => 'Dislipidemia',                 'label_en' => 'Dyslipidemia',                   'sort_order' => 4,  'is_system' => true],
                    ['code' => 'Asthma',         'label_ms' => 'Asma',                         'label_en' => 'Asthma',                         'sort_order' => 5,  'is_system' => true],
                    ['code' => 'COPD',           'label_ms' => 'Penyakit Pulmonari Obstruktif', 'label_en' => 'COPD',                          'sort_order' => 6,  'is_system' => true],
                    ['code' => 'CKD',            'label_ms' => 'Penyakit Buah Pinggang Kronik', 'label_en' => 'Chronic Kidney Disease',        'sort_order' => 7,  'is_system' => true],
                    ['code' => 'Hypothyroid',    'label_ms' => 'Hipotiroidisme',               'label_en' => 'Hypothyroidism',                 'sort_order' => 8,  'is_system' => true],
                    ['code' => 'Hyperthyroid',   'label_ms' => 'Hipertiroidisme',              'label_en' => 'Hyperthyroidism',                'sort_order' => 9,  'is_system' => true],
                    ['code' => 'Gout',           'label_ms' => 'Gaut',                         'label_en' => 'Gout',                           'sort_order' => 10, 'is_system' => true],
                    ['code' => 'Osteoarthritis', 'label_ms' => 'Osteoartritis',                'label_en' => 'Osteoarthritis',                 'sort_order' => 11, 'is_system' => true],
                    ['code' => 'Eczema',         'label_ms' => 'Ekzema',                       'label_en' => 'Eczema',                         'sort_order' => 12, 'is_system' => true],
                    ['code' => 'Depression',     'label_ms' => 'Kemurungan',                   'label_en' => 'Depression',                     'sort_order' => 13, 'is_system' => true],
                    ['code' => 'Anxiety',        'label_ms' => 'Kebimbangan',                  'label_en' => 'Anxiety',                        'sort_order' => 14, 'is_system' => true],
                    ['code' => 'Pregnancy',      'label_ms' => 'Kehamilan',                    'label_en' => 'Pregnancy',                      'sort_order' => 15, 'is_system' => true],
                ],
            ],
            [
                'group' => 'patient', 'slug' => 'status_pesakit', 'sort_order' => 6,
                'name_ms' => 'Status Pesakit', 'name_en' => 'Patient Status',
                'description_ms' => 'Status rekod pesakit', 'description_en' => 'Patient record status',
                'values' => [
                    ['code' => 'active',   'label_ms' => 'Aktif',       'label_en' => 'Active',   'sort_order' => 1, 'is_system' => true],
                    ['code' => 'inactive', 'label_ms' => 'Tidak Aktif', 'label_en' => 'Inactive', 'sort_order' => 2, 'is_system' => true],
                ],
            ],

            // ── TEMUJANJI ───────────────────────────────────────────────────
            [
                'group' => 'appointment', 'slug' => 'jenis_temujanji', 'sort_order' => 1,
                'name_ms' => 'Jenis Temujanji', 'name_en' => 'Appointment Type',
                'description_ms' => 'Jenis temujanji klinik', 'description_en' => 'Clinic appointment type',
                'values' => [
                    ['code' => 'new',             'label_ms' => 'Baru',                  'label_en' => 'New',             'sort_order' => 1, 'is_system' => true],
                    ['code' => 'follow_up',       'label_ms' => 'Susulan',               'label_en' => 'Follow Up',       'sort_order' => 2, 'is_system' => true],
                    ['code' => 'annual_checkup',  'label_ms' => 'Pemeriksaan Tahunan',   'label_en' => 'Annual Checkup',  'sort_order' => 3, 'is_system' => true],
                    ['code' => 'procedure',       'label_ms' => 'Prosedur',              'label_en' => 'Procedure',       'sort_order' => 4, 'is_system' => true],
                    ['code' => 'antenatal',       'label_ms' => 'Antenatal',             'label_en' => 'Antenatal',       'sort_order' => 5, 'is_system' => true],
                    ['code' => 'teleconsult',     'label_ms' => 'Telefonsultasi',        'label_en' => 'Teleconsult',     'sort_order' => 6, 'is_system' => true],
                ],
            ],
            [
                'group' => 'appointment', 'slug' => 'status_temujanji', 'sort_order' => 2,
                'name_ms' => 'Status Temujanji', 'name_en' => 'Appointment Status',
                'description_ms' => 'Status aliran kerja temujanji', 'description_en' => 'Appointment workflow status',
                'values' => [
                    ['code' => 'confirmed',  'label_ms' => 'Disahkan',     'label_en' => 'Confirmed',  'sort_order' => 1, 'is_system' => true],
                    ['code' => 'waiting',    'label_ms' => 'Menunggu',     'label_en' => 'Waiting',    'sort_order' => 2, 'is_system' => true],
                    ['code' => 'in_room',    'label_ms' => 'Dalam Bilik',  'label_en' => 'In Room',    'sort_order' => 3, 'is_system' => true],
                    ['code' => 'done',       'label_ms' => 'Selesai',      'label_en' => 'Done',       'sort_order' => 4, 'is_system' => true],
                    ['code' => 'cancelled',  'label_ms' => 'Dibatalkan',   'label_en' => 'Cancelled',  'sort_order' => 5, 'is_system' => true],
                    ['code' => 'no_show',    'label_ms' => 'Tidak Hadir',  'label_en' => 'No Show',    'sort_order' => 6, 'is_system' => true],
                ],
            ],
            [
                'group' => 'appointment', 'slug' => 'tempoh_temujanji', 'sort_order' => 3,
                'name_ms' => 'Tempoh Temujanji', 'name_en' => 'Appointment Duration',
                'description_ms' => 'Tempoh slot temujanji (minit)', 'description_en' => 'Appointment slot duration (minutes)',
                'values' => [
                    ['code' => '15', 'label_ms' => '15 minit', 'label_en' => '15 minutes', 'sort_order' => 1, 'is_system' => true],
                    ['code' => '30', 'label_ms' => '30 minit', 'label_en' => '30 minutes', 'sort_order' => 2, 'is_system' => true],
                    ['code' => '45', 'label_ms' => '45 minit', 'label_en' => '45 minutes', 'sort_order' => 3, 'is_system' => true],
                    ['code' => '60', 'label_ms' => '60 minit', 'label_en' => '60 minutes', 'sort_order' => 4, 'is_system' => true],
                ],
            ],

            // ── FARMASI ─────────────────────────────────────────────────────
            [
                'group' => 'pharmacy', 'slug' => 'kekerapan_dos', 'sort_order' => 1,
                'name_ms' => 'Kekerapan Dos', 'name_en' => 'Dosage Frequency',
                'description_ms' => 'Kekerapan pengambilan ubat', 'description_en' => 'Medication dosage frequency',
                'values' => [
                    ['code' => 'OD',  'label_ms' => 'OD — 1× sehari',    'label_en' => 'OD — Once Daily',         'sort_order' => 1, 'is_system' => true],
                    ['code' => 'BD',  'label_ms' => 'BD — 2× sehari',    'label_en' => 'BD — Twice Daily',        'sort_order' => 2, 'is_system' => true],
                    ['code' => 'TDS', 'label_ms' => 'TDS — 3× sehari',   'label_en' => 'TDS — Three Times Daily', 'sort_order' => 3, 'is_system' => true],
                    ['code' => 'QID', 'label_ms' => 'QID — 4× sehari',   'label_en' => 'QID — Four Times Daily',  'sort_order' => 4, 'is_system' => true],
                    ['code' => 'ON',  'label_ms' => 'ON — Malam',         'label_en' => 'ON — Night Only',         'sort_order' => 5, 'is_system' => true],
                    ['code' => 'PRN', 'label_ms' => 'PRN — Bila perlu',   'label_en' => 'PRN — As Needed',         'sort_order' => 6, 'is_system' => true],
                ],
            ],
            [
                'group' => 'pharmacy', 'slug' => 'arahan_dos', 'sort_order' => 2,
                'name_ms' => 'Arahan Dos', 'name_en' => 'Dosage Instructions',
                'description_ms' => 'Arahan cara pengambilan ubat', 'description_en' => 'Medication intake instructions',
                'values' => [
                    ['code' => 'after_meal',        'label_ms' => 'Selepas makan',             'label_en' => 'After meal',             'sort_order' => 1, 'is_system' => true],
                    ['code' => 'before_meal',       'label_ms' => 'Sebelum makan',             'label_en' => 'Before meal',            'sort_order' => 2, 'is_system' => true],
                    ['code' => 'morning',           'label_ms' => 'Waktu pagi',                'label_en' => 'Morning',                'sort_order' => 3, 'is_system' => true],
                    ['code' => 'bedtime',           'label_ms' => 'Sebelum tidur',             'label_en' => 'Bedtime',                'sort_order' => 4, 'is_system' => true],
                    ['code' => 'as_needed',         'label_ms' => 'Bila perlu',                'label_en' => 'As needed',              'sort_order' => 5, 'is_system' => true],
                    ['code' => 'before_breakfast',  'label_ms' => '30 min sebelum sarapan',   'label_en' => '30 min before breakfast', 'sort_order' => 6, 'is_system' => true],
                ],
            ],

            // ── INVENTORI ───────────────────────────────────────────────────
            [
                'group' => 'inventory', 'slug' => 'bentuk_ubat', 'sort_order' => 1,
                'name_ms' => 'Bentuk Ubat', 'name_en' => 'Drug Form',
                'description_ms' => 'Bentuk / formulasi ubat', 'description_en' => 'Drug dosage form',
                'values' => [
                    ['code' => 'Tablet',       'label_ms' => 'Tablet',         'label_en' => 'Tablet',      'sort_order' => 1,  'is_system' => true],
                    ['code' => 'Kapsul',       'label_ms' => 'Kapsul',         'label_en' => 'Capsule',     'sort_order' => 2,  'is_system' => true],
                    ['code' => 'MDI',          'label_ms' => 'MDI (Inhaler)',   'label_en' => 'MDI (Inhaler)','sort_order' => 3, 'is_system' => true],
                    ['code' => 'Sirup',        'label_ms' => 'Sirup',          'label_en' => 'Syrup',       'sort_order' => 4,  'is_system' => true],
                    ['code' => 'Serbuk',       'label_ms' => 'Serbuk',         'label_en' => 'Powder',      'sort_order' => 5,  'is_system' => true],
                    ['code' => 'Titis',        'label_ms' => 'Titis',          'label_en' => 'Drops',       'sort_order' => 6,  'is_system' => true],
                    ['code' => 'Suntikan',     'label_ms' => 'Suntikan',       'label_en' => 'Injection',   'sort_order' => 7,  'is_system' => true],
                    ['code' => 'Krim',         'label_ms' => 'Krim',           'label_en' => 'Cream',       'sort_order' => 8,  'is_system' => true],
                    ['code' => 'Gel',          'label_ms' => 'Gel',            'label_en' => 'Gel',         'sort_order' => 9,  'is_system' => true],
                    ['code' => 'Patch',        'label_ms' => 'Patch',          'label_en' => 'Patch',       'sort_order' => 10, 'is_system' => true],
                    ['code' => 'Supositari',   'label_ms' => 'Supositari',     'label_en' => 'Suppository', 'sort_order' => 11, 'is_system' => true],
                ],
            ],
            [
                'group' => 'inventory', 'slug' => 'kategori_ubat', 'sort_order' => 2,
                'name_ms' => 'Kategori Ubat', 'name_en' => 'Drug Category',
                'description_ms' => 'Kategori terapeutik ubat', 'description_en' => 'Drug therapeutic category',
                'values' => [
                    ['code' => 'Antibiotik',         'label_ms' => 'Antibiotik',         'label_en' => 'Antibiotic',        'sort_order' => 1,  'is_system' => true],
                    ['code' => 'Analgesik',          'label_ms' => 'Analgesik',          'label_en' => 'Analgesic',         'sort_order' => 2,  'is_system' => true],
                    ['code' => 'Antidiabetik',       'label_ms' => 'Antidiabetik',       'label_en' => 'Antidiabetic',      'sort_order' => 3,  'is_system' => true],
                    ['code' => 'Antihipertensi',     'label_ms' => 'Antihipertensi',     'label_en' => 'Antihypertensive',  'sort_order' => 4,  'is_system' => true],
                    ['code' => 'Kardiologi',         'label_ms' => 'Kardiologi',         'label_en' => 'Cardiology',        'sort_order' => 5,  'is_system' => true],
                    ['code' => 'Pernafasan',         'label_ms' => 'Pernafasan',         'label_en' => 'Respiratory',       'sort_order' => 6,  'is_system' => true],
                    ['code' => 'Hormon',             'label_ms' => 'Hormon',             'label_en' => 'Hormone',           'sort_order' => 7,  'is_system' => true],
                    ['code' => 'Antihistamin',       'label_ms' => 'Antihistamin',       'label_en' => 'Antihistamine',     'sort_order' => 8,  'is_system' => true],
                    ['code' => 'Gastroenterologi',   'label_ms' => 'Gastroenterologi',   'label_en' => 'Gastroenterology',  'sort_order' => 9,  'is_system' => true],
                    ['code' => 'Saraf',              'label_ms' => 'Saraf',              'label_en' => 'Neurology',         'sort_order' => 10, 'is_system' => true],
                    ['code' => 'Ortopedik',          'label_ms' => 'Ortopedik',          'label_en' => 'Orthopedic',        'sort_order' => 11, 'is_system' => true],
                    ['code' => 'Lain-lain',          'label_ms' => 'Lain-lain',          'label_en' => 'Others',            'sort_order' => 12, 'is_system' => true],
                ],
            ],
            [
                'group' => 'inventory', 'slug' => 'klasifikasi_ubat', 'sort_order' => 3,
                'name_ms' => 'Klasifikasi Ubat', 'name_en' => 'Drug Classification',
                'description_ms' => 'Klasifikasi kawal selia ubat', 'description_en' => 'Drug regulatory classification',
                'values' => [
                    ['code' => 'general',    'label_ms' => 'Umum',      'label_en' => 'General',   'sort_order' => 1, 'is_system' => true],
                    ['code' => 'poison_b',   'label_ms' => 'Racun B',   'label_en' => 'Poison B',  'sort_order' => 2, 'is_system' => true],
                    ['code' => 'poison_c',   'label_ms' => 'Racun C',   'label_en' => 'Poison C',  'sort_order' => 3, 'is_system' => true],
                    ['code' => 'controlled', 'label_ms' => 'Terkawal',  'label_en' => 'Controlled','sort_order' => 4, 'is_system' => true],
                ],
            ],

            // ── BILLING ─────────────────────────────────────────────────────
            [
                'group' => 'billing', 'slug' => 'kaedah_bayaran', 'sort_order' => 1,
                'name_ms' => 'Kaedah Bayaran', 'name_en' => 'Payment Method',
                'description_ms' => 'Kaedah pembayaran bil', 'description_en' => 'Invoice payment method',
                'values' => [
                    ['code' => 'cash',      'label_ms' => 'Tunai',     'label_en' => 'Cash',      'sort_order' => 1, 'is_system' => true],
                    ['code' => 'card',      'label_ms' => 'Kad',       'label_en' => 'Card',      'sort_order' => 2, 'is_system' => true],
                    ['code' => 'duitnow',   'label_ms' => 'DuitNow',   'label_en' => 'DuitNow',   'sort_order' => 3, 'is_system' => true],
                    ['code' => 'panel',     'label_ms' => 'Panel',     'label_en' => 'Panel',     'sort_order' => 4, 'is_system' => true],
                    ['code' => 'insurance', 'label_ms' => 'Insurans',  'label_en' => 'Insurance', 'sort_order' => 5, 'is_system' => true],
                ],
            ],
            [
                'group' => 'billing', 'slug' => 'jenis_item_bil', 'sort_order' => 2,
                'name_ms' => 'Jenis Item Bil', 'name_en' => 'Invoice Item Type',
                'description_ms' => 'Jenis perkhidmatan atau barangan dalam bil', 'description_en' => 'Type of service or item in invoice',
                'values' => [
                    ['code' => 'consultation', 'label_ms' => 'Konsultasi', 'label_en' => 'Consultation', 'sort_order' => 1, 'is_system' => true],
                    ['code' => 'procedure',    'label_ms' => 'Prosedur',   'label_en' => 'Procedure',    'sort_order' => 2, 'is_system' => true],
                    ['code' => 'drug',         'label_ms' => 'Ubat',       'label_en' => 'Drug',         'sort_order' => 3, 'is_system' => true],
                    ['code' => 'lab',          'label_ms' => 'Makmal',     'label_en' => 'Lab',          'sort_order' => 4, 'is_system' => true],
                    ['code' => 'other',        'label_ms' => 'Lain-lain',  'label_en' => 'Other',        'sort_order' => 5, 'is_system' => true],
                ],
            ],

            // ── SURAT RUJUKAN ───────────────────────────────────────────────
            [
                'group' => 'referral', 'slug' => 'keutamaan_rujukan', 'sort_order' => 1,
                'name_ms' => 'Keutamaan Rujukan', 'name_en' => 'Referral Urgency',
                'description_ms' => 'Tahap keutamaan surat rujukan', 'description_en' => 'Referral letter urgency level',
                'values' => [
                    ['code' => 'routine',   'label_ms' => 'Biasa',      'label_en' => 'Routine',   'sort_order' => 1, 'is_system' => true],
                    ['code' => 'urgent',    'label_ms' => 'Segera',     'label_en' => 'Urgent',    'sort_order' => 2, 'is_system' => true],
                    ['code' => 'emergency', 'label_ms' => 'Kecemasan',  'label_en' => 'Emergency', 'sort_order' => 3, 'is_system' => true],
                ],
            ],

            // ── PENGGUNA ────────────────────────────────────────────────────
            [
                'group' => 'user', 'slug' => 'peranan_pengguna', 'sort_order' => 1,
                'name_ms' => 'Peranan Pengguna', 'name_en' => 'User Role',
                'description_ms' => 'Peranan dan akses pengguna sistem', 'description_en' => 'System user role and access',
                'values' => [
                    ['code' => 'doctor',        'label_ms' => 'Doktor',        'label_en' => 'Doctor',        'sort_order' => 1, 'is_system' => true],
                    ['code' => 'nurse',         'label_ms' => 'Jururawat',     'label_en' => 'Nurse',         'sort_order' => 2, 'is_system' => true],
                    ['code' => 'pharmacist',    'label_ms' => 'Ahli Farmasi',  'label_en' => 'Pharmacist',    'sort_order' => 3, 'is_system' => true],
                    ['code' => 'receptionist',  'label_ms' => 'Resepsionis',   'label_en' => 'Receptionist',  'sort_order' => 4, 'is_system' => true],
                    ['code' => 'admin',         'label_ms' => 'Pentadbir',     'label_en' => 'Admin',         'sort_order' => 5, 'is_system' => true],
                ],
            ],
        ];

        foreach ($data as $catData) {
            $values = $catData['values'];
            unset($catData['values']);

            $category = LookupCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            foreach ($values as $v) {
                $category->values()->updateOrCreate(
                    ['code' => $v['code']],
                    $v
                );
            }
        }
    }
}
