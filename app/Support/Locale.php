<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * Idioma de la interfaz.
 *
 * El sistema se escribió en español, así que el español es la lengua base: las
 * llaves de traducción son el texto en español y `lang/en.json` las traduce. Lo
 * que todavía no esté traducido se ve en español, que es un respaldo honesto:
 * nunca sale una llave cruda ni un hueco. Mismo esquema que CargoSuite.
 */
enum Locale: string
{
    case Es = 'es';
    case En = 'en';

    /** Recuerda el idioma de quien todavía no inicia sesión. */
    public const COOKIE = 'app_locale';

    /** Un año, igual que la del tema. */
    public const COOKIE_MINUTES = 525_600;

    /** Preferencia guardada del usuario; sin sesión, la cookie; si no, español. */
    public static function current(): self
    {
        return auth()->user()?->locale
            ?? self::tryFrom((string) Cookie::get(self::COOKIE))
            ?? self::Es;
    }

    public function label(): string
    {
        return match ($this) {
            self::Es => 'Español',
            self::En => 'English',
        };
    }

    /** Las dos letras del botón. */
    public function short(): string
    {
        return strtoupper($this->value);
    }
}
