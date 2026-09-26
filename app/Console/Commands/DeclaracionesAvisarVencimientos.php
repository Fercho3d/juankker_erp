<?php

namespace App\Console\Commands;

use App\Mail\VencimientoDeclaracionMail;
use App\Models\Declaracion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Avisa por correo de las declaraciones presentadas sin pagar cuya línea de
 * captura vence en 3 días, mañana u hoy, o venció ayer. Corre una vez al día,
 * así que cada aviso sale una sola vez.
 */
class DeclaracionesAvisarVencimientos extends Command
{
    protected $signature = 'declaraciones:avisar-vencimientos {--simular : Sólo muestra a quién avisaría}';

    protected $description = 'Avisa por correo cuando se va a vencer el pago de una declaración';

    public function handle(): int
    {
        $hoy = now('America/Mexico_City')->startOfDay();
        $fechas = collect([3, 1, 0, -1])->map(fn ($dias) => $hoy->copy()->addDays($dias)->toDateString());

        $porUsuario = Declaracion::whereNotNull('fecha_presentacion')
            ->whereNull('fecha_pago')
            ->whereIn('vence_pago', $fechas)
            ->orderBy('vence_pago')
            ->get()
            ->groupBy('user_id');

        foreach ($porUsuario as $userId => $declaraciones) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }
            $this->line("{$user->email}: ".$declaraciones->map(fn ($d) => "{$d->año}-".($d->mes ?? 'anual').' vence '.$d->vence_pago->format('d/m/Y'))->join(', '));
            if (! $this->option('simular')) {
                Mail::to($user->email)->send(new VencimientoDeclaracionMail($user, $declaraciones));
            }
        }

        return self::SUCCESS;
    }
}
