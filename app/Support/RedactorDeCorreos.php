<?php

namespace App\Support;

use Anthropic\Client;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Borradores de correo para prospectos con Claude. Para gastar poco: sólo se
 * manda lo que hace falta para personalizar (nunca el teléfono ni el correo),
 * el esfuerzo va en "low", la respuesta es un JSON corto y el mismo contexto
 * no se vuelve a pagar en una semana.
 */
class RedactorDeCorreos
{
    private const INSTRUCCIONES = <<<'TXT'
        Eres vendedor B2B en México. Escribe un correo breve (menos de 120 palabras),
        en español de México, cercano y profesional, para el prospecto descrito.
        Personalízalo con su giro, su tamaño y lo que ya se habló con él. No inventes
        datos, precios, nombres ni cifras que no aparezcan. Cierra con una sola
        pregunta concreta para agendar una llamada o visita. No pongas firma.
        TXT;

    /** Dólares por millón de tokens (entrada, salida) para estimar el gasto en el log. */
    private const PRECIOS = ['haiku' => [1, 5], 'sonnet' => [2, 10], 'opus' => [5, 25]];

    private const FORMATO = [
        'type' => 'json_schema',
        'schema' => [
            'type' => 'object',
            'properties' => ['asunto' => ['type' => 'string'], 'cuerpo' => ['type' => 'string']],
            'required' => ['asunto', 'cuerpo'],
            'additionalProperties' => false,
        ],
    ];

    /**
     * @return array{asunto: string, cuerpo: string}|null null si la empresa ya
     *                                                    gastó su cupo del día
     */
    public function redactar(Lead $lead, string $objetivo): ?array
    {
        $contexto = self::contexto($lead, $objetivo);
        $clave = 'correo-ia:'.md5($contexto);
        $cupo = 'correo-ia:'.$lead->organization_id;

        if (! Cache::has($clave) && ! RateLimiter::attempt($cupo, (int) config('services.anthropic.correos_por_dia'), fn () => true, 86400)) {
            return null;
        }

        return Cache::remember($clave, now()->addWeek(), fn () => $this->pedir($contexto));
    }

    /** @return array{asunto: string, cuerpo: string} */
    private function pedir(string $contexto): array
    {
        $key = config('services.anthropic.key') ?: throw new RuntimeException('Falta ANTHROPIC_API_KEY.');

        $modelo = config('services.anthropic.model');

        $mensaje = (new Client(apiKey: $key))->messages->create(
            model: $modelo,
            maxTokens: 2000,
            system: self::INSTRUCCIONES,
            // Haiku no acepta el parámetro de esfuerzo
            outputConfig: str_contains($modelo, 'haiku')
                ? ['format' => self::FORMATO]
                : ['effort' => 'low', 'format' => self::FORMATO],
            messages: [['role' => 'user', 'content' => $contexto]],
        );

        $precio = collect(self::PRECIOS)->first(fn ($p, $familia) => str_contains($modelo, $familia), [5, 25]);
        $uso = $mensaje->usage;
        Log::info('correo-ia', [
            'modelo' => $modelo,
            'entrada' => $uso->inputTokens,
            'salida' => $uso->outputTokens,
            'usd' => round(($uso->inputTokens * $precio[0] + $uso->outputTokens * $precio[1]) / 1e6, 5),
        ]);

        foreach ($mensaje->content as $bloque) {
            if ($mensaje->stopReason === 'end_turn' && $bloque->type === 'text') {
                return json_decode($bloque->text, true, flags: JSON_THROW_ON_ERROR);
            }
        }

        throw new RuntimeException("Claude no terminó el borrador: {$mensaje->stopReason}");
    }

    private static function contexto(Lead $lead, string $objetivo): string
    {
        $movimientos = $lead->activities()->limit(5)->get()
            ->map(fn ($a) => "- {$a->created_at->format('d/m/Y')} {$a->tipo}: ".Str::limit($a->descripcion, 150))
            ->implode("\n");

        return collect([
            'Qué le ofrecemos' => $objetivo,
            'Contacto' => $lead->nombre,
            'Empresa' => $lead->empresa,
            'Giro' => $lead->giro,
            'Sector' => $lead->sector,
            'Personal' => $lead->rangoPersonal(),
            'Municipio' => $lead->municipio,
            'Etapa' => $lead->stage?->nombre,
            'Notas' => Str::limit((string) $lead->notas, 500),
            'Últimos movimientos' => $movimientos ? "\n".$movimientos : null,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
    }
}
