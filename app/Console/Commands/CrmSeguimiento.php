<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\EnvioDeCorreos;
use App\Support\SeguimientoDeProspectos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

class CrmSeguimiento extends Command
{
    protected $signature = 'crm:seguimiento {--simular : Sólo muestra a quién le tocaría}';

    protected $description = 'Manda el segundo y tercer correo, en el mismo hilo, a los prospectos que no contestaron';

    public function handle(): int
    {
        $dueno = User::where('organization_id', (int) config('services.crm_envio.organizacion'))
            ->orderByRaw('email = ? DESC', [config('services.crm_envio.remitente')])
            ->orderBy('id')
            ->first();
        $tope = (int) config('crm.seguimiento.por_dia');

        if (! $dueno || ! EnvioDeCorreos::conBuzonPropio($dueno) || $tope < 1) {
            $this->warn('Seguimiento apagado: falta el buzón propio o CRM_SEGUIMIENTO_POR_DIA es 0.');

            return self::SUCCESS;
        }

        $enviados = 0;
        foreach (SeguimientoDeProspectos::ordenDeToques() as $toque) {
            foreach (SeguimientoDeProspectos::candidatos($dueno->organization_id, $toque, $tope - $enviados) as $primero) {
                $this->line("toque {$toque} · {$primero->lead->empresa} <".strtolower($primero->lead->email).'>'
                    .' · primer correo '.$primero->enviado_at->diffForHumans());
                if ($this->option('simular')) {
                    continue;
                }
                if (! $this->mandar($primero, $toque, $dueno)) {
                    break 2;
                }
                $enviados++;
                sleep((int) config('crm.envio_diario.pausa_segundos'));
            }
        }

        $this->info($this->option('simular') ? 'Simulación terminada.' : "Seguimientos enviados: {$enviados}.");

        return self::SUCCESS;
    }

    /** false sólo cuando se llegó al tope diario compartido con el envío inicial. */
    private function mandar($primero, int $toque, User $dueno): bool
    {
        if (! RateLimiter::attempt('crm-envio:'.$dueno->organization_id, (int) config('services.crm_envio.por_dia'), fn () => true, 86400)) {
            $this->warn('Se llegó al tope diario de envíos.');

            return false;
        }

        $borrador = SeguimientoDeProspectos::preparar($primero, $toque, $dueno->id);
        // Si falla queda en la bandeja, igual que el envío diario.
        if (EnvioDeCorreos::mandar($borrador, strtolower($primero->lead->email), true, $dueno)) {
            EnvioDeCorreos::registrarEnviado($borrador, $dueno);
        }

        return true;
    }
}
