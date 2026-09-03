<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Valida el captcha de Cloudflare Turnstile.
 * Si TURNSTILE_SECRET está vacío, el captcha se ignora (opcional en dev).
 */
class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret');

        if (empty($secret)) {
            return;
        }

        if (empty($value)) {
            $fail('Completa la verificación de seguridad.');

            return;
        }

        try {
            $resp = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]
            );
        } catch (\Throwable $e) {
            // No bloqueamos a un usuario legítimo si Cloudflare no responde.
            return;
        }

        if (! ($resp->json('success') === true)) {
            $fail('La verificación de seguridad falló. Intenta de nuevo.');
        }
    }
}
