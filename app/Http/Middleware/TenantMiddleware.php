<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resuelve la organización del usuario, bloquea el acceso si la
 * suscripción no está activa y comparte los días de prueba con las vistas.
 */
class TenantMiddleware
{
    /** Rutas que nunca se bloquean (para poder pagar / salir). */
    const EXEMPT = ['pricing.*', 'subscription.*', 'logout', 'profile.*', 'superadmin.*'];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || $user->isSuperadmin()) {
            return $next($request);
        }

        if (! $user->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('Tu acceso a esta organización está desactivado.')]);
        }

        $org = $user->organization;

        if ($org && ! $org->isActive() && ! $this->isExempt($request)) {
            return redirect()->route('pricing')->with('subscription_expired', true);
        }

        if ($org && $org->inTrial()) {
            view()->share('trialDaysRemaining', $org->trialDaysRemaining());
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        foreach (self::EXEMPT as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
