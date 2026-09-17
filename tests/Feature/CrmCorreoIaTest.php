<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Support\RedactorDeCorreos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Nunca llama a la API: el cupo y la caché se prueban antes de la llamada.
 */
class CrmCorreoIaTest extends TestCase
{
    use DatabaseTransactions;

    private function unLead(): Lead
    {
        return Lead::deOrganizacion(User::find(1)->organization_id)->firstOrFail();
    }

    public function test_el_borrador_se_muestra_en_la_ficha(): void
    {
        $lead = $this->unLead();
        config(['services.anthropic.key' => 'llave-de-prueba']);
        $this->mock(RedactorDeCorreos::class)->shouldReceive('redactar')
            ->andReturn(['asunto' => 'Asunto de prueba', 'cuerpo' => 'Cuerpo de prueba']);

        $this->actingAs(User::find(1))
            ->from(route('crm.leads.show', $lead))
            ->followingRedirects()
            ->post(route('crm.leads.correo-ia', $lead), ['objetivo' => 'Demo del ERP'])
            ->assertSee('Asunto de prueba');
    }

    public function test_sin_llave_no_aparece_el_panel(): void
    {
        config(['services.anthropic.key' => null]);

        $this->actingAs(User::find(1))
            ->get(route('crm.leads.show', $this->unLead()))
            ->assertDontSee('Redactar borrador');
    }

    public function test_sin_cupo_no_llama_a_la_ia(): void
    {
        $lead = $this->unLead();
        config(['services.anthropic.correos_por_dia' => 1, 'services.anthropic.key' => null]);
        RateLimiter::clear('correo-ia:'.$lead->organization_id);
        RateLimiter::hit('correo-ia:'.$lead->organization_id, 86400);

        $this->assertNull(app(RedactorDeCorreos::class)->redactar($lead, 'Cupo agotado '.uniqid()));
    }

    public function test_el_mismo_contexto_sale_de_la_cache(): void
    {
        $lead = $this->unLead();
        config(['services.anthropic.key' => null]);
        Cache::shouldReceive('has')->andReturnTrue();
        Cache::shouldReceive('remember')->andReturn(['asunto' => 'Guardado', 'cuerpo' => '']);

        $this->assertSame('Guardado', app(RedactorDeCorreos::class)->redactar($lead, 'Demo')['asunto']);
    }
}
