<?php

namespace App\Console\Commands;

use App\Models\CrmBorrador;
use App\Models\Lead;
use App\Models\User;
use App\Support\EnvioDeCorreos;
use Illuminate\Console\Command;
use Throwable;

class CrmEnviarDiario extends Command
{
    protected $signature = 'crm:enviar-diario {--simular : Sólo muestra a quién le escribiría}';

    protected $description = 'Escribe a los mejores prospectos nuevos con la plantilla aprobada, desde el buzón de la empresa';

    public function handle(): int
    {
        $config = config('crm.envio_diario');
        // Quien firma: el usuario del buzón y, si no existe, el primero de la empresa.
        $dueno = User::where('organization_id', (int) config('services.crm_envio.organizacion'))
            ->orderByRaw('email = ? DESC', [config('services.crm_envio.remitente')])
            ->orderBy('id')
            ->first();

        if (! $dueno || ! EnvioDeCorreos::conBuzonPropio($dueno) || $config['por_dia'] < 1) {
            $this->warn('Envío diario apagado: falta el buzón propio o CRM_AUTOENVIO_POR_DIA es 0.');

            return self::SUCCESS;
        }

        $leads = Lead::deOrganizacion($dueno->organization_id)->abiertos()
            ->whereNotNull('email')
            ->conPresenciaWeb()
            ->whereIn('sector', array_keys($config['listas']))
            ->where(fn ($q) => collect($config['omitir_correos'])->each(fn ($p) => $q->whereRaw('LOWER(email) NOT LIKE ?', ["%{$p}%"])))
            ->whereNull('ultimo_contacto_at')
            ->whereDoesntHave('activities')
            ->whereNotExists(fn ($q) => $q->from('crm_borradores')->whereColumn('crm_borradores.lead_id', 'leads.id'))
            ->mejoresPrimero($dueno->organization_id)
            ->limit($config['por_dia'])
            ->get();

        $resumen = [];
        foreach ($leads as $i => $lead) {
            $this->line(($i + 1).". {$lead->empresa} <".strtolower($lead->email).'>');
            if ($this->option('simular')) {
                continue;
            }
            if (! EnvioDeCorreos::cupoDelDia($lead->organization_id)) {
                $this->warn('Se llegó al tope diario de envíos.');
                break;
            }

            $borrador = CrmBorrador::create([
                'organization_id' => $lead->organization_id,
                'lead_id' => $lead->id,
                'user_id' => $dueno->id,
                'asunto' => $config['asunto'],
                'cuerpo' => strtr($config['cuerpo'], [':lista' => $config['listas'][$lead->sector], ':empresa' => $lead->nombreParaSaludo()]),
            ]);
            // Si falla se queda en la bandeja para mandarlo a mano.
            $enviado = EnvioDeCorreos::mandar($borrador, strtolower($lead->email), true, $dueno);
            if ($enviado) {
                EnvioDeCorreos::registrarEnviado($borrador, $dueno);
            }
            $resumen[] = ($enviado ? '✓ ' : '✗ ')."{$lead->empresa} · {$lead->sector}\n  ".strtolower($lead->email)
                ."\n  ".route('crm.leads.show', $lead);
            sleep($config['pausa_segundos']);
        }

        if ($resumen) {
            $this->avisar($dueno, $resumen);
        }

        $this->info("Prospectos del día: {$leads->count()}.");

        return self::SUCCESS;
    }

    /** Resumen al buzón de la empresa: a quién se le escribió hoy y cuáles fallaron. */
    private function avisar(User $dueno, array $resumen): void
    {
        $buzon = EnvioDeCorreos::remitente($dueno);
        $fallidos = count(array_filter($resumen, fn ($r) => str_starts_with($r, '✗')));
        $texto = 'Hoy se les escribió a estas empresas con la plantilla del envío diario:'."\n\n"
            .implode("\n\n", $resumen)
            .($fallidos ? "\n\n✗ = no se pudo enviar; quedó en ".route('crm.correos').' para mandarlo a mano.' : '')
            ."\n\nEmbudo: ".route('crm.tablero');

        try {
            EnvioDeCorreos::mailer($dueno)
                ->raw($texto, fn ($m) => $m->from($buzon, 'ERP Juancker')->to($buzon)
                    ->subject('Envío diario: '.(count($resumen) - $fallidos).' correos a prospectos'));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
