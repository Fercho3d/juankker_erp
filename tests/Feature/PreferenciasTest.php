<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Locale;
use App\Support\Theme;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tema e idioma: se guardan en la cuenta y en cookie, y la página los respeta.
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class PreferenciasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_visitante_guarda_el_tema_en_cookie(): void
    {
        $this->putJson(route('preferences.theme'), ['theme' => 'dark'])
            ->assertNoContent()
            ->assertCookie(Theme::COOKIE, 'dark');
    }

    public function test_con_sesion_el_tema_y_el_idioma_viajan_con_la_cuenta(): void
    {
        $user = User::find(1);

        $this->actingAs($user)->putJson(route('preferences.theme'), ['theme' => 'dark'])->assertNoContent();
        $this->actingAs($user)->putJson(route('preferences.locale'), ['locale' => 'en'])->assertNoContent();

        $user->refresh();
        $this->assertSame(Theme::Dark, $user->theme);
        $this->assertSame(Locale::En, $user->locale);
    }

    public function test_un_valor_inventado_se_rechaza(): void
    {
        $this->putJson(route('preferences.theme'), ['theme' => 'morado'])->assertUnprocessable();
        $this->putJson(route('preferences.locale'), ['locale' => 'fr'])->assertUnprocessable();
    }

    public function test_la_pagina_sale_en_el_tema_y_el_idioma_de_la_cuenta(): void
    {
        $user = User::find(1);
        $user->update(['theme' => Theme::Dark, 'locale' => Locale::En]);

        $html = $this->actingAs($user)->get('/crm')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<html lang="en"\s+class="dark"/', $html);
    }

    public function test_sin_preferencia_la_app_sale_en_espanol(): void
    {
        $user = User::find(1);
        $user->update(['theme' => null, 'locale' => null]);

        $this->actingAs($user)->get('/crm')->assertOk()->assertSee('<html lang="es"', false);
    }
}
