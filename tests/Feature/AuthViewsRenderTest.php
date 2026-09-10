<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthViewsRenderTest extends TestCase
{
    public function test_las_pantallas_publicas_responden(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $ruta) {
            $this->assertSame(200, $this->get($ruta)->status(), "Falló {$ruta}");
        }
    }

    public function test_el_login_trae_el_boton_de_ver_contrasena(): void
    {
        $this->get('/login')
            ->assertSee('alternarPassword', false)
            ->assertSee('Mostrar contraseña', false);
    }
}
