<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'Log masuk Google gagal. Sila cuba semula.',
            ]);
        }

        $user = User::where('email', $googleUser->getEmail())
            ->orWhere('google_id', $googleUser->getId())
            ->first();

        if (! $user) {
            AuditLog::record('google_login_failed', 'auth', false, [
                'email' => $googleUser->getEmail(),
                'reason' => 'Akaun tidak dijumpai',
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Akaun Google anda tidak berdaftar dalam sistem ini. Sila hubungi pentadbir.',
            ]);
        }

        if ($user->status !== 'active') {
            return redirect()->route('login')->withErrors([
                'email' => 'Akaun anda tidak aktif. Sila hubungi pentadbir.',
            ]);
        }

        // Bind google_id if first time using Google login
        if (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        auth()->login($user, remember: true);

        AuditLog::record('google_login', 'auth', true);

        return redirect()->intended(route('dashboard'));
    }
}
