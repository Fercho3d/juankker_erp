<?php

namespace Tests\Feature;

use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class CrmEnviarDiarioTest extends TestCase
{
    use DatabaseTransactions;

    private int $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = User::find(1)->organization_id;
        config([
            'services.crm_envio.organizacion' => $this->org,
            'services.crm_envio.remitente' => 'contacto@empresa.test',
            'mail.mailers.prospeccion' => ['transport' => 'array', 'password' => 'secreta'],
            'crm.envio_diario.pausa_segundos' => 0,
            'crm.envio_diario.por_dia' => 50,
            'services.crm_envio.por_dia' => 50,
        ]);
        RateLimiter::clear('crm-envio:'.$this->org);
    }

    private function prospecto(string $empresa): Lead
    {
        return Lead::create([
            'organization_id' => $this->org, 'stage_id' => CrmStage::paraOrganizacion($this->org)->first()->id,
            'nombre' => $empresa, 'empresa' => $empresa, 'email' => strtolower($empresa).'@prospecto.test',
            'sector' => 'Manufactura', 'personal_min' => 251, 'origen' => 'prospeccion', 'probabilidad' => 100,
        ]);
    }

    public function test_escribe_al_mejor_prospecto_nuevo_con_la_plantilla(): void
    {
        $lead = $this->prospecto('Fabrica');

        $this->artisan('crm:enviar-diario')->assertSuccessful();

        $this->assertStringContainsString('compras, producción, inventario', CrmBorrador::where('lead_id', $lead->id)->whereNotNull('enviado_at')->value('cuerpo'));
    }

    public function test_salta_los_correos_de_reclutamiento(): void
    {
        $lead = $this->prospecto('Reclutamiento');

        $this->artisan('crm:enviar-diario');

        $this->assertFalse(CrmBorrador::where('lead_id', $lead->id)->exists());
    }

    public function test_no_vuelve_a_escribir_a_quien_ya_se_contacto(): void
    {
        $lead = $this->prospecto('Contactada');
        CrmActivity::create(['organization_id' => $this->org, 'lead_id' => $lead->id, 'tipo' => 'llamada', 'descripcion' => 'Ya se le llamó']);

        $this->artisan('crm:enviar-diario');

        $this->assertFalse(CrmBorrador::where('lead_id', $lead->id)->exists());
    }
}
