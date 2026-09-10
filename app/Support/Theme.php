<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * Tema visual. `System` sigue al sistema operativo y se resuelve en el
 * navegador; los otros dos son explícitos. Mismo esquema que CargoSuite.
 */
enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    /** Recuerda el tema de quien todavía no inicia sesión. */
    public const COOKIE = 'app_theme';

    /**
     * El navegador escribe aquí el tema ya resuelto cuando la preferencia es
     * `System`: el servidor no ve el sistema operativo, y sin este dato pintaría
     * en claro para que el script lo corrigiera con un parpadeo.
     */
    public const RESOLVED_COOKIE = 'app_theme_resolved';

    /** Un año. */
    public const COOKIE_MINUTES = 525_600;

    /** Preferencia guardada del usuario; sin sesión, la cookie; si no, sistema. */
    public static function current(): self
    {
        return auth()->user()?->theme
            ?? self::tryFrom((string) Cookie::get(self::COOKIE))
            ?? self::System;
    }

    /** Claro u oscuro, ya sin el caso `System`: decide la clase `dark` del <html>. */
    public static function resolved(): self
    {
        $tema = self::current();

        if ($tema !== self::System) {
            return $tema;
        }

        return Cookie::get(self::RESOLVED_COOKIE) === self::Dark->value ? self::Dark : self::Light;
    }

    public function label(): string
    {
        return match ($this) {
            self::Light => __('Claro'),
            self::Dark => __('Oscuro'),
            self::System => __('Sistema'),
        };
    }
}
