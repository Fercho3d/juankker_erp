<?php

namespace Tests\Feature;

use App\Models\CrmBorrador;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class CrmCorreosTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::find(1);
        config([
            'services.crm_envio.organizacion' => $this->user->organization_id,
            'services.crm_envio.remitente' => 'contacto@empresa.test',
            'mail.mailers.prospeccion' => ['transport' => 'array', 'password' => 'secreta'],
        ]);
    }

    private function borrador(): CrmBorrador
    {
        $lead = Lead::deOrganizacion($this->user->organization_id)->whereNotNull('email')->firstOrFail();

        return CrmBorrador::create([
            'organization_id' => $lead->organization_id, 'lead_id' => $lead->id,
            'asunto' => 'Asunto de prueba', 'cuerpo' => 'Cuerpo de prueba',
        ]);
    }

    public function test_la_api_deja_el_borrador_en_la_bandeja(): void
    {
        $lead = Lead::deOrganizacion($this->user->organization_id)->firstOrFail();
        Sanctum::actingAs($this->user);
        $this->postJson("/api/crm/leads/{$lead->id}/borradores", ['asunto' => 'Desde la API', 'cuerpo' => 'Hola'])->assertCreated();

        $this->actingAs($this->user)->get(route('crm.correos'))->assertSee('Desde la API');
    }

    public function test_la_api_corrige_un_borrador(): void
    {
        $borrador = $this->borrador();
        Sanctum::actingAs($this->user);

        $this->putJson("/api/crm/borradores/{$borrador->id}", ['cuerpo' => 'Con teléfono'])->assertOk();

        $this->assertSame('Con teléfono', $borrador->fresh()->cuerpo);
    }

    public function test_ya_lo_mande_lo_registra_en_la_bitacora(): void
    {
        $borrador = $this->borrador();

        $this->actingAs($this->user)->post(route('crm.correos.enviado', $borrador));

        $this->assertDatabaseHas('crm_activities', ['lead_id' => $borrador->lead_id, 'tipo' => 'email', 'descripcion' => "Asunto de prueba\n\nCuerpo de prueba"]);
    }

    public function test_enviar_lo_manda_por_el_buzon_de_la_empresa(): void
    {
        $borrador = $this->borrador();

        $this->actingAs($this->user)->post(route('crm.correos.enviar', $borrador));

        $this->assertSame('Asunto de prueba', Mail::mailer('prospeccion')->getSymfonyTransport()->messages()->first()?->getOriginalMessage()->getSubject());
    }

    public function test_la_prueba_llega_al_buzon_propio_y_no_toca_al_prospecto(): void
    {
        $borrador = $this->borrador();

        $this->actingAs($this->user)->post(route('crm.correos.prueba', $borrador));

        $this->assertNull($borrador->fresh()->enviado_at);
    }

    public function test_sin_buzon_propio_sale_por_el_mailer_del_sistema(): void
    {
        $borrador = $this->borrador();
        config(['services.crm_envio.organizacion' => 999, 'mail.default' => 'array', 'mail.from.address' => 'no-reply@empresa.test']);

        $this->actingAs($this->user)->post(route('crm.correos.enviar', $borrador));

        $this->assertSame('no-reply@empresa.test', Mail::mailer('array')->getSymfonyTransport()->messages()->first()?->getOriginalMessage()->getFrom()[0]->getAddress());
    }

    public function test_si_el_vendedor_es_del_dominio_del_sistema_sale_de_su_correo(): void
    {
        $borrador = $this->borrador();
        $dominio = Str::after($this->user->email, '@');
        config(['services.crm_envio.organizacion' => 999, 'mail.default' => 'array', 'mail.from.address' => "no-reply@{$dominio}"]);

        $this->actingAs($this->user)->post(route('crm.correos.enviar', $borrador));

        $this->assertSame($this->user->email, Mail::mailer('array')->getSymfonyTransport()->messages()->first()?->getOriginalMessage()->getFrom()[0]->getAddress());
    }
}
