<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Tests\TestCase;

class CrmSmokeTest extends TestCase
{
    public function test_pantallas_del_crm_responden(): void
    {
        $user = User::find(1);
        $lead = Lead::where('organization_id', $user->organization_id)->first();

        $rutas = [
            '/crm',
            '/crm/tablero',
            '/crm/tablero?sector=Comercio+al+por+menor&tamano=11&contacto=telefono&search=refacc',
            '/crm/leads/nuevo',
            '/crm/importar',
            '/crm/papelera',
            "/crm/leads/{$lead->id}",
            "/crm/leads/{$lead->id}/editar",
        ];

        foreach ($rutas as $ruta) {
            $respuesta = $this->actingAs($user)->get($ruta);
            $this->assertSame(200, $respuesta->status(), "Falló {$ruta} con status ".$respuesta->status());
        }
    }
}
