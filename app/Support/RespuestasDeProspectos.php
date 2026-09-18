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
    /** @return bool si se registró (false si no es de un prospecto o ya estaba) */
    public static function registrar(int $organizationId, string $de, string $asunto, string $texto, string $mensajeId, CarbonInterface $fecha): bool
    {
        $lead = Lead::deOrganizacion($organizationId)->whereRaw('LOWER(email) = ?', [Str::lower($de)])->first();
        $yaEsta = CrmActivity::deOrganizacion($organizationId)->where('mensaje_id', $mensajeId)->exists();

        if (! $lead || $yaEsta) {
            return false;
        }

        CrmActivity::create([
            'organization_id' => $organizationId,
            'lead_id' => $lead->id,
            'tipo' => 'email',
            'descripcion' => Str::limit("Respondió: {$asunto}\n\n".self::sinCitas($texto), 5000),
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

    /** Lo que escribió el prospecto, sin el correo original que viene citado abajo. */
    public static function sinCitas(string $texto): string
    {
        $corte = preg_split('/^(>|El .+escribi[oó]:|On .+wrote:|-{2,} ?(Mensaje original|Original Message))/miu', $texto, 2);

        return trim($corte[0] ?? $texto);
    }
}
