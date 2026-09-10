<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja la petición en el idioma que eligió quien la hace. Va al final del grupo
 * web, después de la sesión, porque la preferencia vive en el usuario.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $idioma = Locale::current()->value;

        app()->setLocale($idioma);

        // Las fechas con letra («hace 2 días») las arma Carbon con su propio idioma.
        Carbon::setLocale($idioma);

        return $next($request);
    }
}
