<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard', ['currentRoute' => 'dashboard']))->name('dashboard');
    Route::get('/queue',        fn () => Inertia::render('Queue',        ['currentRoute' => 'queue']))->name('queue');
    Route::get('/register-patient', fn () => Inertia::render('Register', ['currentRoute' => 'register']))->name('register-patient');
    // Patients — CRUD
    Route::get('/patients',            [PatientController::class, 'index'])->name('patients');
    Route::post('/patients',           [PatientController::class, 'store'])->name('patients.store');
    Route::put('/patients/{patient}',  [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}',[PatientController::class, 'destroy'])->name('patients.destroy');
    // Appointments — CRUD
    Route::get('/appointments',                        [AppointmentController::class, 'index'])->name('appointments');
    Route::post('/appointments',                       [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}',          [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::delete('/appointments/{appointment}',       [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::get('/emr',          fn () => Inertia::render('EMR',          ['currentRoute' => 'emr']))->name('emr');
    // Pharmacy — CRUD
    Route::get('/pharmacy',                                    [PharmacyController::class, 'index'])->name('pharmacy');
    Route::post('/pharmacy/prescriptions',                     [PharmacyController::class, 'store'])->name('pharmacy.store');
    Route::put('/pharmacy/prescriptions/{prescription}',       [PharmacyController::class, 'update'])->name('pharmacy.update');
    Route::patch('/pharmacy/prescriptions/{prescription}/status', [PharmacyController::class, 'updateStatus'])->name('pharmacy.status');
    Route::delete('/pharmacy/prescriptions/{prescription}',    [PharmacyController::class, 'destroy'])->name('pharmacy.destroy');
    // Inventory — CRUD
    Route::get('/inventory',                                      [InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory',                                     [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{inventoryItem}',                      [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{inventoryItem}/stock',              [InventoryController::class, 'adjustStock'])->name('inventory.stock');
    Route::delete('/inventory/{inventoryItem}',                   [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/billing',      fn () => Inertia::render('Billing',      ['currentRoute' => 'billing']))->name('billing');
    Route::get('/reports',      fn () => Inertia::render('Reports',      ['currentRoute' => 'reports']))->name('reports');

    // Settings — CRUD
    Route::get('/settings',                [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/users',         [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::put('/settings/users/{user}',   [SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}',[SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
    Route::put('/settings/policies',       [SettingsController::class, 'updatePolicies'])->name('settings.policies.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
