<?php

namespace Tests\Feature;

use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\User;
use App\Support\SeguimientoDeProspectos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Message;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class CrmSeguimientoTest extends TestCase
{
    use DatabaseTransactions;

    private int $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = User::find(1)->organization_id;
        config(['crm.seguimiento.dias' => [2 => 3, 3 => 7]]);
    }

    /** Prospecto en "Contactado" con su primer correo enviado hace N días. */
    private function contactadoHace(int $dias, string $email = 'ventas@seguimiento.test'): CrmBorrador
    {
        $etapas = CrmStage::paraOrganizacion($this->org);
        $lead = Lead::create([
            'organization_id' => $this->org, 'stage_id' => $etapas->skip(1)->first()->id,
            'nombre' => 'Prueba', 'empresa' => 'Empresa '.$dias, 'email' => $email, 'origen' => 'otro',
            'sector' => 'Manufactura', 'probabilidad' => 10, 'ultimo_contacto_at' => now()->subDays($dias),
        ]);

        return CrmBorrador::create([
            'organization_id' => $this->org, 'lead_id' => $lead->id, 'user_id' => 1,
            'asunto' => 'Juancker · Prueba', 'cuerpo' => 'Hola', 'toque' => 1,
            'message_id' => 'primero@prueba', 'enviado_at' => now()->subDays($dias),
        ]);
    }

    private function candidatos(int $toque): array
    {
        return SeguimientoDeProspectos::candidatos($this->org, $toque, 50)->pluck('lead_id')->all();
    }

    public function test_a_los_tres_dias_sin_respuesta_toca_el_segundo_correo(): void
    {
        $primero = $this->contactadoHace(3);

        $this->assertContains($primero->lead_id, $this->candidatos(2));
        $this->assertNotContains($primero->lead_id, $this->candidatos(3));
    }

    public function test_antes_de_los_tres_dias_no_toca_nada(): void
    {
        $primero = $this->contactadoHace(2);

        $this->assertNotContains($primero->lead_id, $this->candidatos(2));
    }

    public function test_a_los_siete_dias_toca_el_tercero_y_no_repite_el_segundo(): void
    {
        $primero = $this->contactadoHace(7);
        SeguimientoDeProspectos::preparar($primero, 2, 1)->update(['enviado_at' => now()->subDays(4)]);

        $this->assertContains($primero->lead_id, $this->candidatos(3));
        $this->assertNotContains($primero->lead_id, $this->candidatos(2));
    }

    public function test_si_el_prospecto_contesto_se_detiene(): void
    {
        $primero = $this->contactadoHace(3);
        CrmActivity::create(['organization_id' => $this->org, 'lead_id' => $primero->lead_id, 'tipo' => 'email',
            'descripcion' => 'Respondió', 'mensaje_id' => 'resp@prueba', 'completada_at' => now()]);

        $this->assertNotContains($primero->lead_id, $this->candidatos(2));
    }

    public function test_si_alguien_ya_lo_atendio_se_detiene(): void
    {
        $primero = $this->contactadoHace(3);
        CrmActivity::create(['organization_id' => $this->org, 'lead_id' => $primero->lead_id, 'user_id' => 1,
            'tipo' => 'llamada', 'descripcion' => 'Hablé con el dueño', 'completada_at' => now()]);

        $this->assertNotContains($primero->lead_id, $this->candidatos(2));
    }

    public function test_si_cambio_de_etapa_se_detiene(): void
    {
        $primero = $this->contactadoHace(3);
        $primero->lead->update(['stage_id' => CrmStage::paraOrganizacion($this->org)->skip(2)->first()->id]);

        $this->assertNotContains($primero->lead_id, $this->candidatos(2));
    }

    public function test_el_seguimiento_va_en_el_mismo_hilo_con_su_lista_por_sector(): void
    {
        $primero = $this->contactadoHace(3);

        $borrador = SeguimientoDeProspectos::preparar($primero, 2, 1);
        $mensaje = new Message(new Email);
        $id = SeguimientoDeProspectos::encabezadosDeHilo($mensaje, $borrador, 'contacto@juancker.com');

        $this->assertSame('Re: Juancker · Prueba', $borrador->asunto);
        $this->assertSame('primero@prueba', $borrador->responde_a);
        $this->assertStringContainsString('compras, producción, inventario y facturación CFDI 4.0', $borrador->cuerpo);
        $headers = $mensaje->getSymfonyMessage()->getHeaders();
        $this->assertSame('<primero@prueba>', $headers->get('In-Reply-To')->getBodyAsString());
        $this->assertSame('<'.$id.'>', $headers->get('Message-ID')->getBodyAsString());
        $this->assertStringEndsWith('@juancker.com', $id);
    }

    public function test_el_tope_se_respeta_y_van_primero_los_mas_antiguos(): void
    {
        $viejo = $this->contactadoHace(5, 'a@tope.test');
        $nuevo = $this->contactadoHace(3, 'b@tope.test');

        $ids = SeguimientoDeProspectos::candidatos($this->org, 2, 1)->pluck('lead_id')->all();

        $this->assertSame([$viejo->lead_id], $ids);
        $this->assertNotContains($nuevo->lead_id, $ids);
    }
}
