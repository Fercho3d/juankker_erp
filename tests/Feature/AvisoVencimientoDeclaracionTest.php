<?php

namespace Tests\Feature;

use App\Mail\VencimientoDeclaracionMail;
use App\Models\Declaracion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AvisoVencimientoDeclaracionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_avisa_sólo_de_lo_presentado_sin_pagar_que_vence_en_3_días_mañana_hoy_o_ayer(): void
    {
        Mail::fake();
        Declaracion::query()->delete();
        $user = User::firstOrFail();
        $hoy = now('America/Mexico_City')->startOfDay();
        $crear = fn ($mes, $dias, $pagada = false) => Declaracion::create([
            'user_id' => $user->id, 'año' => 2020, 'mes' => $mes, 'fecha_presentacion' => $hoy->copy()->subDays(10),
            'fecha_pago' => $pagada ? $hoy : null, 'vence_pago' => $hoy->copy()->addDays($dias), 'monto_linea_captura' => 1000,
        ]);
        $crear(1, 3);
        $crear(2, 0);
        $crear(3, 2);
        $crear(4, 1, pagada: true);

        $this->artisan('declaraciones:avisar-vencimientos')->assertSuccessful();

        Mail::assertSent(VencimientoDeclaracionMail::class, fn ($m) => $m->declaraciones->pluck('mes')->sort()->values()->all() === [1, 2]);
    }
}
