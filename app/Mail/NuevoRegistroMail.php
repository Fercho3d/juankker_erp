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
     * Notifica al admin interno silenciando errores de envío.
     */
    public static function notificar(User $user, Organization $organization): void
    {
        $to = env('ADMIN_NOTIFY_EMAIL', config('mail.from.address'));

        if (empty($to)) {
            return;
        }

        rescue(fn () => Mail::to($to)->send(new self($user, $organization)), null, false);
    }

    public function build()
    {
        return $this->subject("Nuevo registro ERP: {$this->organization->name}")
            ->view('emails.nuevo-registro');
    }
}
