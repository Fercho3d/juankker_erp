<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rules\Enum;

/**
 * Guarda tema e idioma: en la cuenta si hay sesión —para que viajen con ella—
 * y en una cookie siempre, para que la pantalla de acceso también los recuerde.
 */
class PreferenceController extends Controller
{
    public function theme(Request $request): Response
    {
        $tema = Theme::from($request->validate(['theme' => ['required', new Enum(Theme::class)]])['theme']);

        $request->user()?->update(['theme' => $tema]);
        Cookie::queue(Theme::COOKIE, $tema->value, Theme::COOKIE_MINUTES);

        return response()->noContent();
    }

    public function locale(Request $request): Response
    {
        $idioma = Locale::from($request->validate(['locale' => ['required', new Enum(Locale::class)]])['locale']);

        $request->user()?->update(['locale' => $idioma]);
        Cookie::queue(Locale::COOKIE, $idioma->value, Locale::COOKIE_MINUTES);

        return response()->noContent();
    }
}
