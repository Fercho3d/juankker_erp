<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NuevoRegistroMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Organization $organization)
    {
    }

    /**
     * Notifica al admin interno. Un fallo de envío no debe tumbar el registro,
     * pero sí queda en el log para enterarse.
     *
     * Se lee de config y no de env(): con la configuración cacheada, env()
     * devuelve null fuera de config/ y el aviso se iba al remitente no-reply.
     */
    public static function notificar(User $user, Organization $organization): void
    {
        $to = config('services.avisos.registro');

        if (empty($to)) {
            logger()->warning('Registro sin aviso: falta ADMIN_NOTIFY_EMAIL.', ['organization_id' => $organization->id]);

            return;
        }

        rescue(fn () => Mail::to($to)->send(new self($user, $organization)), null, true);
    }

    public function build()
    {
        return $this->subject("Nuevo registro ERP: {$this->organization->name}")
            ->view('emails.nuevo-registro');
    }
}
