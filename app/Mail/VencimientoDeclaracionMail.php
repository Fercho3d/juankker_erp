<?php

namespace App\Mail;

use App\Models\Declaracion;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VencimientoDeclaracionMail extends Mailable
{
    /** @param  Collection<int, Declaracion>  $declaraciones */
    public function __construct(public User $user, public Collection $declaraciones) {}

    /** Días de calendario (hora de México) que faltan para que venza la línea de captura; negativo si ya venció. */
    public static function diasParaVencer(Declaracion $declaracion): int
    {
        $hoy = Carbon::parse(now('America/Mexico_City')->toDateString());

        return (int) $hoy->diffInDays(Carbon::parse($declaracion->vence_pago->toDateString()), false);
    }

    public function build()
    {
        $vencida = $this->declaraciones->contains(fn ($d) => self::diasParaVencer($d) < 0);

        return $this->subject($vencida ? 'Se venció el pago de una declaración' : 'Se vence el pago de tu declaración')
            ->view('emails.vencimiento-declaracion');
    }
}
