<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitacionEquipoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TeamInvitation $invitacion, public string $invitadoPor)
    {
    }

    public function build()
    {
        return $this->subject(__(':nombre te invitó a su equipo en Juankker ERP', ['nombre' => $this->invitadoPor]))
            ->view('emails.invitacion-equipo');
    }
}
