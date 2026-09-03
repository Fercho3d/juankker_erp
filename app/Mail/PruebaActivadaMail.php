<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PruebaActivadaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Organization $organization)
    {
    }

    public function build()
    {
        return $this->subject('Tu prueba gratuita de 30 días está activa 🎉')
            ->view('emails.prueba-activada');
    }
}
