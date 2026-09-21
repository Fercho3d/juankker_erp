<?php

namespace App\Support;

use App\Models\CrmActivity;
use App\Models\Lead;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Pasa a la bitácora los correos que los prospectos mandan al buzón de la
 * empresa, y deja la respuesta como lo primero que hay que atender.
 */
class RespuestasDeProspectos
{
    /** Dominios de correo personal: ahí un mismo dominio no identifica a una empresa. */
    private const DOMINIOS_PERSONALES = [
        'gmail.com', 'hotmail.com', 'hotmail.es', 'outlook.com', 'outlook.es', 'live.com', 'live.com.mx',
        'yahoo.com', 'yahoo.com.mx', 'icloud.com', 'msn.com', 'prodigy.net.mx', 'me.com', 'aol.com',
    ];

    /** @return bool si se registró (false si no es de un prospecto o ya estaba) */
    public static function registrar(int $organizationId, string $de, string $asunto, string $texto, string $mensajeId, CarbonInterface $fecha): bool
    {
        $de = Str::lower(trim($de));
        $lead = self::prospectoDe($organizationId, $de);
        $yaEsta = CrmActivity::deOrganizacion($organizationId)->where('mensaje_id', $mensajeId)->exists();

        if (! $lead || $yaEsta) {
            return false;
        }

        // Si contestó otra persona de la empresa, que quede claro quién.
        $quien = Str::lower($lead->email) === $de ? '' : " (desde {$de})";

        CrmActivity::create([
            'organization_id' => $organizationId,
            'lead_id' => $lead->id,
            'tipo' => 'email',
            'descripcion' => Str::limit("Respondió{$quien}: {$asunto}\n\n".self::sinCitas($texto), 5000),
            'mensaje_id' => $mensajeId,
            'completada_at' => $fecha,
        ]);
        $lead->update([
            'ultimo_contacto_at' => $fecha,
            'proxima_accion' => 'Contestar su correo',
            'proxima_accion_at' => now(),
        ]);

        return true;
    }

    /**
     * Primero por correo exacto. Si no, por dominio de la empresa: es común que
     * se escriba a ventas@ y conteste el dueño desde su propio correo. Sólo entre
     * prospectos ya contactados, para no atribuir correos ajenos.
     */
    private static function prospectoDe(int $organizationId, string $de): ?Lead
    {
        $exacto = Lead::deOrganizacion($organizationId)->whereRaw('LOWER(email) = ?', [$de])->first();
        if ($exacto) {
            return $exacto;
        }

        $dominio = Str::after($de, '@');
        if ($dominio === '' || $dominio === $de || in_array($dominio, self::DOMINIOS_PERSONALES, true)) {
            return null;
        }

        return Lead::deOrganizacion($organizationId)
            ->whereNotNull('ultimo_contacto_at')
            ->whereRaw('LOWER(email) LIKE ?', ['%@'.$dominio])
            ->orderByDesc('ultimo_contacto_at')
            ->first();
    }

    /** Lo que escribió el prospecto, sin el correo original que viene citado abajo. */
    public static function sinCitas(string $texto): string
    {
        $corte = preg_split('/^(>|El .+escribi[oó]:|On .+wrote:|-{2,} ?(Mensaje original|Original Message))/miu', $texto, 2);

        return trim($corte[0] ?? $texto);
    }
}
