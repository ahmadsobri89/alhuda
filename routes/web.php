<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\EMRController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MCController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::post('/locale', [\App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/queue', [QueueController::class, 'index'])->name('queue');
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
    // EMR — CRUD
    Route::get('/emr',                                         [EMRController::class, 'index'])->name('emr');
    Route::post('/emr',                                        [EMRController::class, 'store'])->name('emr.store');
    Route::patch('/emr/{visit}/soap',                          [EMRController::class, 'updateSoap'])->name('emr.soap');
    Route::post('/emr/{visit}/vitals',                         [EMRController::class, 'storeVitals'])->name('emr.vitals');
    Route::post('/emr/{visit}/diagnoses',                      [EMRController::class, 'storeDiagnosis'])->name('emr.diagnoses.store');
    Route::delete('/emr/{visit}/diagnoses/{diagnosis}',        [EMRController::class, 'destroyDiagnosis'])->name('emr.diagnoses.destroy');
    Route::patch('/emr/{visit}/close',                         [EMRController::class, 'close'])->name('emr.close');
    Route::delete('/emr/{visit}',                              [EMRController::class, 'destroy'])->name('emr.destroy');
    // MC — Medical Certificates
    Route::post('/emr/{visit}/mc',  [MCController::class, 'store'])->name('mc.store');
    Route::delete('/mc/{mc}',       [MCController::class, 'destroy'])->name('mc.destroy');
    Route::get('/mc/{mc}/print',    [MCController::class, 'print'])->name('mc.print');
    // Pharmacy — CRUD
    Route::get('/pharmacy',                                    [PharmacyController::class, 'index'])->name('pharmacy');
    Route::post('/pharmacy/prescriptions',                     [PharmacyController::class, 'store'])->name('pharmacy.store');
    Route::put('/pharmacy/prescriptions/{prescription}',       [PharmacyController::class, 'update'])->name('pharmacy.update');
    Route::patch('/pharmacy/prescriptions/{prescription}/status', [PharmacyController::class, 'updateStatus'])->name('pharmacy.status');
    Route::delete('/pharmacy/prescriptions/{prescription}',    [PharmacyController::class, 'destroy'])->name('pharmacy.destroy');
    Route::get('/pharmacy/prescriptions/{prescription}/print', [PharmacyController::class, 'print'])->name('pharmacy.print');
    Route::get('/pharmacy/prescriptions/{prescription}/label', [PharmacyController::class, 'label'])->name('pharmacy.label');
    // Inventory — CRUD
    Route::get('/inventory',                                      [InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory',                                     [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{inventoryItem}',                      [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{inventoryItem}/stock',              [InventoryController::class, 'adjustStock'])->name('inventory.stock');
    Route::delete('/inventory/{inventoryItem}',                   [InventoryController::class, 'destroy'])->name('inventory.destroy');
    // Billing — CRUD
    Route::get('/billing',                                [BillingController::class, 'index'])->name('billing');
    Route::post('/billing',                               [BillingController::class, 'store'])->name('billing.store');
    Route::post('/billing/{invoice}/items',               [BillingController::class, 'storeItem'])->name('billing.items.store');
    Route::delete('/billing/{invoice}/items/{item}',      [BillingController::class, 'destroyItem'])->name('billing.items.destroy');
    Route::patch('/billing/{invoice}/discount',           [BillingController::class, 'updateDiscount'])->name('billing.discount');
    Route::patch('/billing/{invoice}/finalize',           [BillingController::class, 'finalize'])->name('billing.finalize');
    Route::patch('/billing/{invoice}/pay',                [BillingController::class, 'pay'])->name('billing.pay');
    Route::patch('/billing/{invoice}/cancel',             [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::delete('/billing/{invoice}',                   [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::get('/billing/{invoice}/print',                [BillingController::class, 'print'])->name('billing.print');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

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
