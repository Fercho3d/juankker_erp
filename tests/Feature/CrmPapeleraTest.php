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
class CrmPapeleraTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::find(1);
    }

    private function unLead(): Lead
    {
        return Lead::deOrganizacion($this->user->organization_id)->whereNotNull('telefono')->firstOrFail();
    }

    public function test_descartar_lo_saca_del_embudo_y_lo_manda_a_la_papelera(): void
    {
        $lead = $this->unLead();
        $antes = Lead::deOrganizacion($lead->organization_id)->count();

        $this->actingAs($this->user)
            ->postJson(route('crm.leads.descartar', $lead), ['motivo' => 'ya_tiene_sistema'])
            ->assertOk();

        $this->assertSoftDeleted($lead);
        $this->assertSame($antes - 1, Lead::deOrganizacion($lead->organization_id)->count());
        $this->actingAs($this->user)->get('/crm/tablero')->assertOk()->assertDontSee($lead->empresa);
        $this->actingAs($this->user)->get('/crm/papelera')->assertOk()
            ->assertSee($lead->empresa)->assertSee('Ya tiene sistema');
    }

    public function test_un_motivo_inventado_se_rechaza(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('crm.leads.descartar', $this->unLead()), ['motivo' => 'lo_que_sea'])
            ->assertUnprocessable();
    }

    public function test_restaurar_lo_regresa_al_embudo_sin_motivo(): void
    {
        $lead = $this->unLead();
        $lead->update(['motivo_descarte' => 'otro']);
        $lead->delete();

        $this->actingAs($this->user)->post(route('crm.leads.restaurar', $lead->id))->assertRedirect();

        $lead = Lead::find($lead->id);
        $this->assertNotNull($lead, 'El lead restaurado no volvió al embudo');
        $this->assertNull($lead->motivo_descarte);
    }

    public function test_pendientes_no_truena_con_la_actividad_de_un_descartado(): void
    {
        $lead = $this->unLead();
        CrmActivity::create([
            'organization_id' => $lead->organization_id, 'lead_id' => $lead->id,
            'user_id' => $this->user->id, 'tipo' => 'llamada',
            'descripcion' => 'Llamar', 'programada_at' => now(),
        ]);
        $lead->delete();

        $this->actingAs($this->user)->get('/crm')->assertOk();
    }

    public function test_el_alta_masiva_no_revive_a_un_descartado(): void
    {
        $lead = $this->unLead();
        $lead->delete();
        $conEseTelefono = fn () => Lead::withTrashed()->deOrganizacion($lead->organization_id)
            ->where('telefono', $lead->telefono)->count();
        $antes = $conEseTelefono();

        $this->actingAs($this->user)
            ->post(route('crm.leads.importar'), ['datos' => "Otra razon social, , {$lead->telefono}, "])
            ->assertRedirect();

        $this->assertSame($antes, $conEseTelefono());
    }
}
