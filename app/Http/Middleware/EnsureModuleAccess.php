<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Sekat akses ke modul mengikut peranan pengguna. Modul diperoleh dari
     * awalan nama route (lihat config/access.php → route_module). Route yang
     * tiada pemetaan modul tidak disekat.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if ($name) {
            $prefix = explode('.', $name)[0];
            $module = config("access.route_module.{$prefix}");

            if ($module && ($user = $request->user()) && ! $user->canAccessModule($module)) {
                abort(403, 'Anda tiada kebenaran untuk modul ini.');
            }
        }

        return $next($request);
    }
}
