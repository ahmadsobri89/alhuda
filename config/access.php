<?php

/*
|--------------------------------------------------------------------------
| Access control — peranan (roles) → modul
|--------------------------------------------------------------------------
| Satu pengguna boleh ada BERBILANG peranan (lihat users.roles JSON).
| Pengguna boleh akses sesuatu modul jika mana-mana peranannya tersenarai
| di bawah. '*' bermaksud semua pengguna yang log masuk. Peranan 'admin'
| sentiasa boleh akses segala (lihat User::canAccessModule).
*/

return [

    'roles' => ['doctor', 'nurse', 'pharmacist', 'receptionist', 'admin'],

    // Modul → peranan yang dibenarkan
    'modules' => [
        'dashboard'    => ['*'],
        'queue'        => ['*'],
        'register'     => ['receptionist', 'admin'],
        'patients'     => ['receptionist', 'doctor', 'nurse', 'pharmacist', 'admin'],
        'appointments' => ['receptionist', 'doctor', 'nurse', 'admin'],
        'emr'          => ['doctor', 'nurse', 'admin'],
        'pharmacy'     => ['pharmacist', 'admin'],
        'inventory'    => ['pharmacist', 'admin'],
        'billing'      => ['receptionist', 'admin'],
        'reports'      => ['admin', 'doctor'],
        'settings'     => ['admin'],
        'profile'      => ['*'],
    ],

    /*
     | Pemetaan awalan nama route → modul. Kunci ialah segmen pertama nama
     | route (sebelum titik). Nama route yang tiada di sini TIDAK disekat
     | (cth aliran auth: login, logout, password.*).
     */
    'route_module' => [
        'dashboard'        => 'dashboard',
        'queue'            => 'queue',
        'register-patient' => 'register',
        'patients'         => 'patients',
        'appointments'     => 'appointments',
        'emr'              => 'emr',
        'mc'               => 'emr',
        'referral'         => 'emr',
        'timeslip'         => 'emr',
        'quarantine'       => 'emr',
        'pharmacy'         => 'pharmacy',
        'inventory'        => 'inventory',
        'billing'          => 'billing',
        'reports'          => 'reports',
        'settings'         => 'settings',
        'lookup'           => 'settings',
        'profile'          => 'profile',
        'locale'           => 'profile',
    ],
];
