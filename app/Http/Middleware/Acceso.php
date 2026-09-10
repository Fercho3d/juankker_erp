<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar sólo a quien su perfil le da ese módulo.
 * Uso: ->middleware('acceso:crm'). El plan se revisa aparte, con `premium`.
 */
class Acceso
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $user = $request->user();

        if ($user && ! $user->activo) {
            abort(403, __('Tu acceso a esta organización está desactivado.'));
        }

        if ($user && ! $user->puede($modulo)) {
            if ($request->expectsJson()) {
                abort(403, __('Tu perfil no tiene acceso a esta sección.'));
            }

            return redirect($user->inicio())->with('status', __('Tu perfil no tiene acceso a esa sección.'));
        }

        return $next($request);
    }
}
