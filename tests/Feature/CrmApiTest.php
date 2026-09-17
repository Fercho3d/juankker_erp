<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_los_mejores_prospectos_con_correo_salen_por_la_api(): void
    {
        Sanctum::actingAs(User::find(1));

        $correos = collect($this->getJson('/api/crm/leads?orden=mejores&sin_seguimiento=1&con_email=1')
            ->assertOk()->json('data'))->pluck('email');

        $this->assertNotContains(null, $correos);
    }
}
