<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuscripcionActivadaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Organization $organization, public Plan $plan)
    {
    }

    public function build()
    {
        return $this->subject("Tu plan {$this->plan->name} está activo")
            ->view('emails.suscripcion-activada');
    }
}
