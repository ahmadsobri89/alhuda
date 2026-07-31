<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\ClinicProfile;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user'    => $request->user(),
                'modules' => $request->user()?->accessibleModules() ?? [],
            ],
            'flash' => [
                'success'        => fn () => $request->session()->get('success'),
                'error'          => fn () => $request->session()->get('error'),
                'quickPatientId' => fn () => $request->session()->get('quickPatientId'),
            ],
            'notifications' => [
                'unreadCount' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            'pendingCounts' => [
                'queue'    => fn () => Appointment::whereDate('appointment_date', now()->toDateString())
                    ->whereIn('status', ['confirmed', 'waiting', 'in_room'])
                    ->count(),
                'emr'      => fn () => Visit::where('status', 'open')->count(),
                'pharmacy' => fn () => Prescription::whereIn('status', ['pending', 'verifying'])->count(),
                'billing'  => fn () => Invoice::whereIn('status', ['draft', 'unpaid'])->count(),
            ],
            'locale'       => app()->getLocale(),
            'translations' => fn () => $this->loadTranslations(app()->getLocale()),
            'clinic'       => function () {
                $cp = ClinicProfile::current();
                return [
                    'name'     => $cp->name,
                    'logo_url' => $cp->logo_url,
                ];
            },
        ];
    }

    private function loadTranslations(string $locale): array
    {
        $path = resource_path("lang/{$locale}.json");
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }
}
