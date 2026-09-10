<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bloquea módulos Premium para organizaciones en plan gratuito.
 * Uso: ->middleware('premium') o ->middleware('premium:inventario')
 * para exigir además que el plan incluya ese módulo.
 */
class Premium
{
    public function handle(Request $request, Closure $next, ?string $module = null)
    {
        $user = Auth::user();

        if ($user?->isSuperadmin()) {
            return $next($request);
        }

        $org = $user?->organization;
        $blocked = $org && ! $org->esPremium();

        if (! $blocked && $module) {
            $blocked = ! $org->allowsModule($module);
        }

        if ($blocked) {
            return redirect()->route('pricing')
                ->with('premium_required', __('Esta función es Premium. Activa un plan para desbloquearla.'));
        }

        return $next($request);
    }
}
