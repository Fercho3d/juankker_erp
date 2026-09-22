<?php

namespace App\Support;

use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use App\Models\CrmStage;
use Illuminate\Mail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Secuencia de seguimiento a prospectos que recibieron el primer correo y no
 * contestaron. Cada toque sale en el mismo hilo que el anterior.
 */
class SeguimientoDeProspectos
{
    /** Actividades que indican que una persona ya tomó el prospecto. */
    private const ATENCION_HUMANA = ['llamada', 'whatsapp', 'visita', 'nota'];

    /**
     * Prospectos a los que hoy les toca el toque indicado: siguen en
     * "Contactado", recibieron el primer correo hace al menos los días
     * configurados, no tienen ese toque, nadie les ha contestado ni atendido.
     *
     * @return Collection<int, CrmBorrador> el primer correo de cada uno
     */
    public static function candidatos(int $organizationId, int $toque, int $limite): Collection
    {
        $dias = config("crm.seguimiento.dias.$toque");
        $contactado = CrmStage::paraOrganizacion($organizationId)->skip(1)->first();

        if ($dias === null || ! $contactado) {
            return collect();
        }

        return CrmBorrador::where('organization_id', $organizationId)
            ->where('toque', 1)
            ->where('enviado_at', '<=', now()->subDays($dias))
            ->whereNotExists(fn ($q) => $q->from('crm_borradores as s')
                ->whereColumn('s.lead_id', 'crm_borradores.lead_id')->where('s.toque', $toque))
            ->whereHas('lead', fn ($q) => $q->where('stage_id', $contactado->id)->whereNotNull('email'))
            ->with('lead')
            ->orderBy('enviado_at')
            ->get()
            ->reject(fn ($primero) => self::alguienIntervino($primero))
            ->take($limite)
            ->values();
    }

    /** Contestó el prospecto, o alguien de la empresa ya lo está atendiendo. */
    private static function alguienIntervino(CrmBorrador $primero): bool
    {
        return CrmActivity::where('lead_id', $primero->lead_id)
            ->where(fn ($q) => $q->whereNotNull('mensaje_id')
                ->orWhere(fn ($h) => $h->whereIn('tipo', self::ATENCION_HUMANA)->where('created_at', '>', $primero->enviado_at)))
            ->exists();
    }

    /** El borrador del toque, enganchado al último correo del hilo. */
    public static function preparar(CrmBorrador $primero, int $toque, ?int $userId): CrmBorrador
    {
        $lead = $primero->lead;
        $lista = config('crm.envio_diario.listas')[$lead->sector] ?? 'la administración';
        $ultimo = CrmBorrador::where('lead_id', $lead->id)->whereNotNull('message_id')->latest('enviado_at')->first();

        return CrmBorrador::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'asunto' => Str::startsWith($primero->asunto, 'Re: ') ? $primero->asunto : 'Re: '.$primero->asunto,
            'cuerpo' => strtr(config("crm.seguimiento.cuerpos.$toque"), [':lista' => $lista, ':empresa' => $lead->nombreParaSaludo()]),
            'toque' => $toque,
            'responde_a' => $ultimo?->message_id,
        ]);
    }

    /**
     * Message-ID propio (para que las respuestas se puedan enlazar) y, si
     * contesta a otro correo, los encabezados que lo dejan en el mismo hilo.
     */
    public static function encabezadosDeHilo(Message $mensaje, CrmBorrador $borrador, string $remitente): string
    {
        $id = (string) Str::uuid().'@'.Str::after($remitente, '@');
        $headers = $mensaje->getSymfonyMessage()->getHeaders();
        $headers->addIdHeader('Message-ID', $id);

        if ($borrador->responde_a) {
            $headers->addIdHeader('In-Reply-To', $borrador->responde_a);
            $headers->addIdHeader('References', $borrador->responde_a);
        }

        return $id;
    }

    /** Correos del toque, de la más antigua a la más reciente, sin exceder el tope. */
    public static function ordenDeToques(): array
    {
        return collect(config('crm.seguimiento.dias'))->sortDesc()->keys()->all();
    }
}
