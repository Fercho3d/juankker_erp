<?php

namespace Tests\Feature;

use App\Models\CrmActivity;
use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\User;
use App\Support\RespuestasDeProspectos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class CrmRespuestasTest extends TestCase
{
    use DatabaseTransactions;

    private function lead(): Lead
    {
        return Lead::deOrganizacion(User::find(1)->organization_id)->whereNotNull('email')->firstOrFail();
    }

    private function registrar(Lead $lead, string $de, string $id = 'msg-1@prueba'): bool
    {
        return RespuestasDeProspectos::registrar($lead->organization_id, $de, 'Re: Juancker', "Sí me interesa.\n\nEl lun, 18 sep escribió:\n> Buen día", $id, now());
    }

    public function test_la_respuesta_de_un_prospecto_queda_en_su_bitacora(): void
    {
        $lead = $this->lead();

        $this->registrar($lead, strtoupper($lead->email));

        $this->assertDatabaseHas('crm_activities', ['lead_id' => $lead->id, 'mensaje_id' => 'msg-1@prueba', 'descripcion' => "Respondió: Re: Juancker\n\nSí me interesa."]);
    }

    public function test_el_mismo_correo_no_se_registra_dos_veces(): void
    {
        $lead = $this->lead();
        $this->registrar($lead, $lead->email);

        $this->assertFalse($this->registrar($lead, $lead->email));
    }

    public function test_un_correo_que_no_es_de_un_prospecto_se_ignora(): void
    {
        $this->assertFalse($this->registrar($this->lead(), 'alguien@desconocido.test'));
    }

    private function contactado(string $email): Lead
    {
        $org = User::find(1)->organization_id;

        return Lead::create([
            'organization_id' => $org, 'stage_id' => CrmStage::paraOrganizacion($org)->first()->id,
            'nombre' => 'Prueba', 'empresa' => 'Prueba', 'email' => $email, 'origen' => 'otro',
            'probabilidad' => 10, 'ultimo_contacto_at' => now()->subDay(),
        ]);
    }

    public function test_si_contesta_otra_persona_de_la_misma_empresa_se_reconoce_por_dominio(): void
    {
        $lead = $this->contactado('ventas@empresa-prueba.test');

        $this->assertTrue($this->registrar($lead, 'gerente@empresa-prueba.test', 'msg-dominio@prueba'));
        $this->assertDatabaseHas('crm_activities', ['lead_id' => $lead->id, 'mensaje_id' => 'msg-dominio@prueba']);
        $this->assertStringContainsString('(desde gerente@empresa-prueba.test)', CrmActivity::where('mensaje_id', 'msg-dominio@prueba')->value('descripcion'));
    }

    public function test_un_dominio_de_correo_personal_no_identifica_a_la_empresa(): void
    {
        $lead = $this->contactado('taller@gmail.com');

        $this->assertFalse($this->registrar($lead, 'otra-persona@gmail.com', 'msg-gmail@prueba'));
    }

    public function test_el_dominio_solo_cuenta_si_el_prospecto_ya_fue_contactado(): void
    {
        $lead = $this->contactado('ventas@nunca-contactado.test');
        $lead->update(['ultimo_contacto_at' => null]);

        $this->assertFalse($this->registrar($lead, 'gerente@nunca-contactado.test', 'msg-nunca@prueba'));
    }

    public function test_un_prospecto_de_otra_empresa_no_se_toca(): void
    {
        $otra = 4;
        $ajeno = Lead::create([
            'organization_id' => $otra, 'stage_id' => CrmStage::paraOrganizacion($otra)->first()->id,
            'nombre' => 'Ajeno', 'email' => 'ajeno@otra.test', 'origen' => 'otro', 'probabilidad' => 5,
        ]);

        RespuestasDeProspectos::registrar(User::find(1)->organization_id, $ajeno->email, 'Re', 'Hola', 'msg-ajeno@prueba', now());

        $this->assertFalse(CrmActivity::where('lead_id', $ajeno->id)->where('mensaje_id', 'msg-ajeno@prueba')->exists());
    }
}
