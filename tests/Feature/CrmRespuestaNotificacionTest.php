<?php

namespace Tests\Feature;

use App\Models\CrmActivity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class CrmRespuestaNotificacionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::find(1);
        $this->user->forceFill(['respuestas_vistas_at' => now()->subMinute()])->save();
    }

    private function respuesta(): CrmActivity
    {
        $lead = Lead::deOrganizacion($this->user->organization_id)->firstOrFail();

        return CrmActivity::create([
            'organization_id' => $lead->organization_id, 'lead_id' => $lead->id, 'tipo' => 'email',
            'descripcion' => 'Respondió: Re: Juancker', 'mensaje_id' => 'msg-'.uniqid(), 'completada_at' => now(),
        ]);
    }

    public function test_la_campana_cuenta_las_respuestas_nuevas(): void
    {
        $this->respuesta();

        $this->actingAs($this->user)->getJson(route('crm.respuestas.nuevas'))->assertJsonPath('nuevas', 1);
    }

    public function test_al_abrir_las_respuestas_dejan_de_ser_nuevas(): void
    {
        $this->respuesta();
        $this->actingAs($this->user)->get(route('crm.respuestas'))->assertSee('Re: Juancker');

        $this->getJson(route('crm.respuestas.nuevas'))->assertJsonPath('nuevas', 0);
    }

    public function test_la_campana_sale_en_la_barra(): void
    {
        $this->actingAs($this->user)->get(route('crm.pendientes'))->assertSee('data-respuestas', false);
    }
}
