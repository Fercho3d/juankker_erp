<?php

namespace App\Console\Commands;

use App\Models\CrmBorrador;
use App\Models\Lead;
use App\Models\User;
use App\Support\EnvioDeCorreos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

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
            ->whereIn('sector', array_keys($config['listas']))
            ->where(fn ($q) => collect($config['omitir_correos'])->each(fn ($p) => $q->whereRaw('LOWER(email) NOT LIKE ?', ["%{$p}%"])))
            ->whereNull('ultimo_contacto_at')
            ->whereDoesntHave('activities')
            ->whereNotExists(fn ($q) => $q->from('crm_borradores')->whereColumn('crm_borradores.lead_id', 'leads.id'))
            ->mejoresPrimero($dueno->organization_id)
            ->limit($config['por_dia'])
            ->get();

        foreach ($leads as $i => $lead) {
            $this->line(($i + 1).". {$lead->empresa} <".strtolower($lead->email).'>');
            if ($this->option('simular')) {
                continue;
            }
            if (! RateLimiter::attempt('crm-envio:'.$lead->organization_id, (int) config('services.crm_envio.por_dia'), fn () => true, 86400)) {
                $this->warn('Se llegó al tope diario de envíos.');
                break;
            }

            $borrador = CrmBorrador::create([
                'organization_id' => $lead->organization_id,
                'lead_id' => $lead->id,
                'user_id' => $dueno->id,
                'asunto' => $config['asunto'],
                'cuerpo' => str_replace(':lista', $config['listas'][$lead->sector], $config['cuerpo']),
            ]);
            // Si falla se queda en la bandeja para mandarlo a mano.
            if (EnvioDeCorreos::mandar($borrador, strtolower($lead->email), true, $dueno)) {
                EnvioDeCorreos::registrarEnviado($borrador, $dueno);
            }
            sleep($config['pausa_segundos']);
        }

        $this->info("Prospectos del día: {$leads->count()}.");

        return self::SUCCESS;
    }
}
