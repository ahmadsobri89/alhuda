<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * Bungkus ImpersonateManager (lab404/laravel-impersonate) dengan route POST.
 * Route terbina package (Route::impersonate) guna GET — terdedah kepada CSRF.
 */
class ImpersonationController extends Controller
{
    public function __construct(private ImpersonateManager $manager) {}

    /** Mula menyamar sebagai pengguna lain. */
    public function start(Request $request, User $user)
    {
        $actor = $request->user();

        if (! $actor->canImpersonate()) {
            AuditLog::record('impersonate.denied', "User #{$user->id} · {$user->name}", false);
            abort(403, 'Anda tiada kebenaran untuk impersonate.');
        }
        if ($actor->is($user) || ! $user->canBeImpersonated()) {
            return back()->with('error', "Pengguna {$user->name} tidak boleh di-impersonate.");
        }

        AuditLog::record('impersonate.start', "User #{$user->id} · {$user->name}");
        $actor->impersonate($user);

        return redirect()->route('dashboard')->with('success', "Anda kini menyamar sebagai {$user->name}.");
    }

    /** Kembali ke akaun asal. */
    public function stop(Request $request)
    {
        if (! $this->manager->isImpersonating()) {
            return redirect()->route('dashboard');
        }

        $target = $request->user();
        $this->manager->leave();

        AuditLog::record('impersonate.stop', "User #{$target?->id} · {$target?->name}");

        return redirect()->route('settings')->with('success', 'Sesi impersonate ditamatkan.');
    }
}
