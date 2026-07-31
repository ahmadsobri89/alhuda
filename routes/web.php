<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EMRController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HealthTipController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MCController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuarantineController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TimeSlipController;
use App\Http\Middleware\EnsureModuleAccess;
use App\Models\ClinicProfile;
use App\Models\HealthTip;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing page (awam — sebelum log masuk)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    $clinic = ClinicProfile::current();

    $tips = HealthTip::active()->ordered()->get(['id', 'title', 'image_path'])
        ->map(fn ($tip) => [
            'id' => $tip->id,
            'title' => $tip->title,
            'image_url' => $tip->image_url,
        ]);

    $testimonials = Testimonial::active()->ordered()->get();

    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'clinic' => [
            'name' => $clinic->name,
            'tagline' => $clinic->tagline,
            'reg_number' => $clinic->reg_number,
            'ckaps_number' => $clinic->ckaps_number,
            'address_full' => $clinic->address_full,
            'phone' => $clinic->phone,
            'fax' => $clinic->fax,
            'email' => $clinic->email,
            'website' => $clinic->website,
            'logo_url' => $clinic->logo_url,
            'waze_url' => $clinic->waze_url,
            'google_maps_url' => $clinic->google_maps_url,
        ],
        'tips' => $tips,
        'testimonials' => $testimonials,
    ]);
})->name('landing');

// Public document verification (no auth required)
Route::get('/verify/timeslip/{token}', [TimeSlipController::class, 'verify'])->name('timeslip.verify');
Route::get('/verify/mc/{token}', [MCController::class, 'verify'])->name('mc.verify');
Route::get('/verify/referral/{token}', [ReferralController::class, 'verify'])->name('referral.verify');
Route::get('/verify/quarantine/{token}', [QuarantineController::class, 'verify'])->name('quarantine.verify');
Route::get('/verify/memo/{token}', [MemoController::class, 'verify'])->name('memo.verify');

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Public testimonial submission (awam — tanpa log masuk, disemak admin dahulu)
Route::post('/testimoni', [TestimonialController::class, 'submitPublic'])
    ->middleware('throttle:5,1')
    ->name('testimonials.submit');

Route::middleware(['auth', 'verified', EnsureModuleAccess::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/queue', [QueueController::class, 'index'])->name('queue');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::delete('/notifications/read', [NotificationController::class, 'clearRead'])->name('notifications.clearRead');
    Route::get('/register-patient', [RegisterController::class, 'index'])->name('register-patient');
    // Patients — CRUD
    Route::get('/patients', [PatientController::class, 'index'])->name('patients');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    // Appointments — CRUD
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::post('/appointments/{appointment}/emr', [AppointmentController::class, 'startEmr'])->name('appointments.emr');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    // EMR — CRUD
    Route::get('/emr', [EMRController::class, 'index'])->name('emr');
    Route::post('/emr', [EMRController::class, 'store'])->name('emr.store');
    Route::patch('/emr/{visit}/soap', [EMRController::class, 'updateSoap'])->name('emr.soap');
    Route::post('/emr/{visit}/vitals', [EMRController::class, 'storeVitals'])->name('emr.vitals');
    Route::post('/emr/{visit}/diagnoses', [EMRController::class, 'storeDiagnosis'])->name('emr.diagnoses.store');
    Route::delete('/emr/{visit}/diagnoses/{diagnosis}', [EMRController::class, 'destroyDiagnosis'])->name('emr.diagnoses.destroy');
    Route::patch('/emr/{visit}/close', [EMRController::class, 'close'])->name('emr.close');
    Route::patch('/emr/{visit}/reopen', [EMRController::class, 'reopen'])->name('emr.reopen');
    Route::delete('/emr/{visit}', [EMRController::class, 'destroy'])->name('emr.destroy');
    Route::post('/emr/{visit}/prescription', [EMRController::class, 'storePrescription'])->name('emr.prescription.store');
    Route::delete('/emr/prescriptions/{prescription}', [EMRController::class, 'destroyPrescription'])->name('emr.prescription.destroy');
    Route::patch('/emr/prescription-items/{item}', [EMRController::class, 'updatePrescriptionItem'])->name('emr.prescription.item.update');
    Route::delete('/emr/prescription-items/{item}', [EMRController::class, 'destroyPrescriptionItem'])->name('emr.prescription.item.destroy');
    // EMR — Services (billing)
    Route::post('/emr/{visit}/services', [EMRController::class, 'storeService'])->name('emr.service.store');
    Route::delete('/emr/{visit}/services/{item}', [EMRController::class, 'destroyService'])->name('emr.service.destroy');
    // MC — Medical Certificates
    Route::post('/emr/{visit}/mc', [MCController::class, 'store'])->name('mc.store');
    Route::delete('/mc/{mc}', [MCController::class, 'destroy'])->name('mc.destroy');
    Route::get('/mc/{mc}/print', [MCController::class, 'print'])->name('mc.print');
    // Referral Letters
    Route::post('/emr/{visit}/referral', [ReferralController::class, 'store'])->name('referral.store');
    Route::delete('/referral/{referral}', [ReferralController::class, 'destroy'])->name('referral.destroy');
    Route::get('/referral/{referral}/print', [ReferralController::class, 'print'])->name('referral.print');
    // Time Slips
    Route::post('/emr/{visit}/timeslip', [TimeSlipController::class, 'store'])->name('timeslip.store');
    Route::delete('/timeslip/{timeslip}', [TimeSlipController::class, 'destroy'])->name('timeslip.destroy');
    Route::get('/timeslip/{timeslip}/print', [TimeSlipController::class, 'print'])->name('timeslip.print');
    // Quarantine Letters
    Route::post('/emr/{visit}/quarantine', [QuarantineController::class, 'store'])->name('quarantine.store');
    Route::delete('/quarantine/{quarantine}', [QuarantineController::class, 'destroy'])->name('quarantine.destroy');
    Route::get('/quarantine/{quarantine}/print', [QuarantineController::class, 'print'])->name('quarantine.print');
    // Memos
    Route::post('/emr/{visit}/memo', [MemoController::class, 'store'])->name('memo.store');
    Route::delete('/memo/{memo}', [MemoController::class, 'destroy'])->name('memo.destroy');
    Route::get('/memo/blank/print', [MemoController::class, 'printBlank'])->name('memo.print_blank');
    Route::get('/memo/{memo}/print', [MemoController::class, 'print'])->name('memo.print');
    // Pharmacy — CRUD
    Route::get('/pharmacy', [PharmacyController::class, 'index'])->name('pharmacy');
    Route::post('/pharmacy/prescriptions', [PharmacyController::class, 'store'])->name('pharmacy.store');
    Route::post('/pharmacy/quick-patient', [PharmacyController::class, 'quickCreatePatient'])->name('pharmacy.quickPatient');
    Route::put('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'update'])->name('pharmacy.update');
    Route::patch('/pharmacy/prescriptions/{prescription}/status', [PharmacyController::class, 'updateStatus'])->name('pharmacy.status');
    Route::delete('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'destroy'])->name('pharmacy.destroy');
    Route::get('/pharmacy/prescriptions/{prescription}/print', [PharmacyController::class, 'print'])->name('pharmacy.print');
    Route::get('/pharmacy/prescriptions/{prescription}/label', [PharmacyController::class, 'label'])->name('pharmacy.label');
    // Inventory — CRUD
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{inventoryItem}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{inventoryItem}/stock', [InventoryController::class, 'adjustStock'])->name('inventory.stock');
    Route::delete('/inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    // Services — CRUD (katalog perkhidmatan/prosedur)
    Route::get('/services', [ServicesController::class, 'index'])->name('services');
    Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServicesController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServicesController::class, 'destroy'])->name('services.destroy');
    // Billing — CRUD
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::post('/billing/{invoice}/items', [BillingController::class, 'storeItem'])->name('billing.items.store');
    Route::patch('/billing/{invoice}/items/{item}', [BillingController::class, 'updateItem'])->name('billing.items.update');
    Route::delete('/billing/{invoice}/items/{item}', [BillingController::class, 'destroyItem'])->name('billing.items.destroy');
    Route::patch('/billing/{invoice}/discount', [BillingController::class, 'updateDiscount'])->name('billing.discount');
    Route::patch('/billing/{invoice}/finalize', [BillingController::class, 'finalize'])->name('billing.finalize');
    Route::patch('/billing/{invoice}/pay', [BillingController::class, 'pay'])->name('billing.pay');
    Route::patch('/billing/{invoice}/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::delete('/billing/{invoice}', [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::get('/billing/{invoice}/print', [BillingController::class, 'print'])->name('billing.print');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

    // Finance — pemantauan pembayaran (harian/bulanan/tahunan)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::get('/finance/export', [FinanceController::class, 'export'])->name('finance.export');

    // Settings — CRUD
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
    Route::put('/settings/policies', [SettingsController::class, 'updatePolicies'])->name('settings.policies.update');
    Route::post('/settings/clinic', [SettingsController::class, 'updateClinic'])->name('settings.clinic.update');

    // Tips Kesihatan — CRUD
    Route::post('/settings/tips', [HealthTipController::class, 'store'])->name('settings.tips.store');
    Route::post('/settings/tips/{healthTip}', [HealthTipController::class, 'update'])->name('settings.tips.update');
    Route::patch('/settings/tips/{healthTip}/toggle', [HealthTipController::class, 'toggle'])->name('settings.tips.toggle');
    Route::delete('/settings/tips/{healthTip}', [HealthTipController::class, 'destroy'])->name('settings.tips.destroy');

    // Testimoni Pesakit — CRUD
    Route::post('/settings/testimonials', [TestimonialController::class, 'store'])->name('settings.testimonials.store');
    Route::put('/settings/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('settings.testimonials.update');
    Route::patch('/settings/testimonials/{testimonial}/toggle', [TestimonialController::class, 'toggle'])->name('settings.testimonials.toggle');
    Route::delete('/settings/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('settings.testimonials.destroy');

    // Lookup Parameters — CRUD
    Route::post('/settings/lookup/{category}/values', [LookupController::class, 'storeValue'])->name('lookup.values.store');
    Route::put('/settings/lookup/{category}/values/{value}', [LookupController::class, 'updateValue'])->name('lookup.values.update');
    Route::delete('/settings/lookup/{category}/values/{value}', [LookupController::class, 'destroyValue'])->name('lookup.values.destroy');
    Route::patch('/settings/lookup/{category}/values/{value}/toggle', [LookupController::class, 'toggleValue'])->name('lookup.values.toggle');

    // Audit Log — jejak aktiviti sistem (admin sahaja)
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
