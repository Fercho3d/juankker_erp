<?php

namespace Tests\Feature;

use App\Mail\NuevoRegistroMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AvisoRegistroTest extends TestCase
{
    public function test_el_aviso_de_registro_va_al_correo_configurado(): void
    {
        Mail::fake();
        config(['services.avisos.registro' => 'dueno@ejemplo.com']);

        NuevoRegistroMail::notificar(new User(['name' => 'Ana', 'email' => 'ana@cliente.com']), new Organization(['name' => 'Cliente SA']));

        Mail::assertQueued(NuevoRegistroMail::class, fn ($m) => $m->hasTo('dueno@ejemplo.com'));
    }

    public function test_sin_correo_configurado_no_se_manda_al_remitente_no_reply(): void
    {
        Mail::fake();
        config(['services.avisos.registro' => null, 'mail.from.address' => 'no-reply@ejemplo.com']);

        NuevoRegistroMail::notificar(new User(['name' => 'Ana', 'email' => 'ana@cliente.com']), new Organization(['name' => 'Cliente SA']));

        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }
}
