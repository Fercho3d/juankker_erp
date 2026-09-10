<?php

namespace Tests\Feature;

use App\Mail\InvitacionEquipoMail;
use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Perfiles de acceso, alcance del embudo e invitaciones al equipo.
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class EquipoTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::find(1);   // usuario de antes de los perfiles: acceso completo
        Role::paraOrganizacion($this->admin->organization_id);
    }

    private function perfil(string $nombre): Role
    {
        return Role::deOrganizacion($this->admin->organization_id)->where('nombre', $nombre)->firstOrFail();
    }

    private function miembro(string $perfil, array $extra = []): User
    {
        return User::create($extra + [
            'name' => $perfil.' '.uniqid(),
            'email' => uniqid().'@prueba.test',
            'password' => bcrypt('Secreta123'),
            'organization_id' => $this->admin->organization_id,
            'role_id' => $this->perfil($perfil)->id,
        ]);
    }

    private function prospecto(User $dueno, array $extra = []): Lead
    {
        return Lead::create($extra + [
            'organization_id' => $dueno->organization_id,
            'stage_id' => CrmStage::paraOrganizacion($dueno->organization_id)->first()->id,
            'owner_id' => $dueno->id,
            'nombre' => 'Prospecto '.uniqid(),
            'empresa' => 'Empresa '.uniqid(),
            'origen' => 'prospeccion',
            'probabilidad' => 10,
        ]);
    }

    /* -------------------- Alcance del embudo -------------------- */

    public function test_un_vendedor_solo_ve_sus_prospectos(): void
    {
        $ana = $this->miembro('Vendedor');
        $beto = $this->miembro('Vendedor');
        $deAna = $this->prospecto($ana);
        $deBeto = $this->prospecto($beto);

        $this->actingAs($ana)->get('/crm/tablero')->assertOk()
            ->assertSee($deAna->empresa)->assertDontSee($deBeto->empresa);
        $this->actingAs($ana)->get("/crm/leads/{$deBeto->id}")->assertForbidden();
        $this->actingAs($ana)->post(route('crm.leads.descartar', $deBeto))->assertForbidden();
    }

    public function test_por_la_api_tampoco_ve_los_de_otros(): void
    {
        $ana = $this->miembro('Vendedor');
        $deAna = $this->prospecto($ana);
        $ajeno = $this->prospecto($this->miembro('Vendedor'));

        Sanctum::actingAs($ana);
        $ids = collect($this->getJson('/api/crm/leads')->assertOk()->json('data') ?? $this->getJson('/api/crm/leads')->json())
            ->pluck('id');

        $this->assertContains($deAna->id, $ids);
        $this->assertNotContains($ajeno->id, $ids);
        $this->getJson("/api/crm/leads/{$ajeno->id}")->assertNotFound();
    }

    public function test_un_gerente_ve_todo_el_embudo(): void
    {
        $gerente = $this->miembro('Gerente de ventas');
        $ajeno = $this->prospecto($this->miembro('Vendedor'));

        $this->actingAs($gerente)->get("/crm/leads/{$ajeno->id}")->assertOk();
    }

    public function test_un_vendedor_no_reparte_ni_se_quita_prospectos(): void
    {
        $ana = $this->miembro('Vendedor');
        $beto = $this->miembro('Vendedor');
        $deAna = $this->prospecto($ana);

        $this->actingAs($ana)->post(route('crm.leads.asignar', $deAna), ['owner_id' => $beto->id])->assertForbidden();
        $this->actingAs($ana)->post(route('crm.leads.asignar-bloque'), ['owner_id' => $beto->id])->assertForbidden();
        $this->actingAs($ana)->put(route('crm.leads.update', $deAna), [
            'nombre' => $deAna->nombre, 'origen' => 'prospeccion', 'probabilidad' => 10, 'owner_id' => $beto->id,
        ])->assertRedirect();

        $this->assertSame($ana->id, $deAna->fresh()->owner_id);
    }

    public function test_el_gerente_reparte_en_bloque_solo_lo_filtrado(): void
    {
        $gerente = $this->miembro('Gerente de ventas');
        $ana = $this->miembro('Vendedor');
        $sector = 'Sector de prueba '.uniqid();
        $dentro = $this->prospecto($gerente, ['sector' => $sector]);
        $fuera = $this->prospecto($gerente, ['sector' => 'Otro '.uniqid()]);

        $this->actingAs($gerente)->post(route('crm.leads.asignar-bloque'), ['sector' => $sector, 'owner_id' => $ana->id])
            ->assertRedirect();

        $this->assertSame($ana->id, $dentro->fresh()->owner_id);
        $this->assertSame($gerente->id, $fuera->fresh()->owner_id);
    }

    /* -------------------- Acceso por módulo -------------------- */

    public function test_sin_el_modulo_lo_manda_a_su_inicio(): void
    {
        $ana = $this->miembro('Vendedor');

        $this->actingAs($ana)->get('/productos')->assertRedirect(route('crm.pendientes'));
        $this->actingAs($ana)->getJson('/productos')->assertForbidden();
        $this->actingAs($ana)->get('/suscripcion')->assertRedirect(route('crm.pendientes'));
        $this->actingAs($ana)->get('/equipo')->assertRedirect(route('crm.pendientes'));
    }

    public function test_el_menu_muestra_solo_lo_permitido(): void
    {
        $html = $this->actingAs($this->miembro('Vendedor'))->get('/crm')->assertOk()->getContent();

        $this->assertStringContainsString(route('crm.tablero'), $html);
        $this->assertStringContainsString(route('clientes.index'), $html);
        $this->assertStringNotContainsString(route('productos.index'), $html);
        $this->assertStringNotContainsString(route('team.index'), $html);
    }

    public function test_un_perfil_personalizado_da_exactamente_lo_marcado(): void
    {
        $this->actingAs($this->admin)->post(route('team.role.store'), [
            'nombre' => 'Sólo catálogo', 'permisos' => ['productos'], 'crm_alcance' => 'todos',
        ])->assertRedirect(route('team.index'));

        $catalogo = $this->miembro('Sólo catálogo');

        $this->actingAs($catalogo)->get('/productos')->assertOk();
        $this->actingAs($catalogo)->get('/crm')->assertRedirect(route('productos.index'));
        $this->actingAs($catalogo)->get('/')->assertRedirect(route('productos.index'));
    }

    public function test_un_permiso_inventado_se_rechaza(): void
    {
        $this->actingAs($this->admin)->post(route('team.role.store'), [
            'nombre' => 'Raro', 'permisos' => ['superpoderes'], 'crm_alcance' => 'todos',
        ])->assertSessionHasErrors('permisos.0');
    }

    /* -------------------- Invitaciones -------------------- */

    public function test_la_invitacion_completa_de_punta_a_punta(): void
    {
        Mail::fake();
        $correo = uniqid().'@prueba.test';

        $this->actingAs($this->admin)->post(route('team.invite'), ['email' => $correo, 'role_id' => $this->perfil('Vendedor')->id])
            ->assertSessionHasNoErrors();
        // ShouldQueue: con Mail::fake queda en cola; en producción la cola es sync y sale al instante.
        Mail::assertQueued(InvitacionEquipoMail::class, fn ($m) => $m->hasTo($correo));

        $token = TeamInvitation::where('email', $correo)->firstOrFail()->token;
        auth()->logout();

        $this->get(route('team.join', $token))->assertOk()->assertSee($correo);
        $this->post(route('team.join.accept', $token), [
            'name' => 'Nueva Vendedora', 'password' => 'Secreta123', 'password_confirmation' => 'Secreta123',
        ])->assertRedirect(route('crm.pendientes'));

        $nueva = User::where('email', $correo)->firstOrFail();
        $this->assertSame($this->admin->organization_id, $nueva->organization_id);
        $this->assertSame('Vendedor', $nueva->role->nombre);
        $this->assertAuthenticatedAs($nueva);

        // El mismo enlace no sirve dos veces
        auth()->logout();
        $this->get(route('team.join', $token))->assertRedirect(route('login'));
    }

    public function test_una_invitacion_vencida_no_sirve(): void
    {
        $inv = (new TeamInvitation(['organization_id' => $this->admin->organization_id, 'role_id' => $this->perfil('Vendedor')->id, 'email' => uniqid().'@prueba.test']))->renovar();
        $inv->expires_at = now()->subDay();
        $inv->save();

        $this->post(route('team.join.accept', $inv->token), [
            'name' => 'X', 'password' => 'Secreta123', 'password_confirmation' => 'Secreta123',
        ])->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['email' => $inv->email]);
    }

    public function test_el_cupo_del_plan_se_respeta(): void
    {
        Mail::fake();
        $this->admin->organization->update(['max_users' => 1]);

        $this->actingAs($this->admin)->post(route('team.invite'), ['email' => uniqid().'@prueba.test', 'role_id' => $this->perfil('Vendedor')->id])
            ->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_no_se_puede_invitar_con_un_perfil_de_otra_empresa(): void
    {
        $ajeno = Role::paraOrganizacion(4)->first();

        $this->actingAs($this->admin)->post(route('team.invite'), ['email' => uniqid().'@prueba.test', 'role_id' => $ajeno->id])
            ->assertSessionHasErrors('role_id');
    }

    /* -------------------- Miembros -------------------- */

    public function test_nadie_se_cambia_a_si_mismo_el_perfil(): void
    {
        $gerente = $this->miembro('Administrador');

        $this->actingAs($gerente)->put(route('team.member.update', $gerente), ['role_id' => $this->perfil('Vendedor')->id])
            ->assertForbidden();
    }

    public function test_un_desactivado_pierde_la_sesion_y_no_puede_entrar(): void
    {
        $ana = $this->miembro('Vendedor');
        $this->actingAs($this->admin)->post(route('team.member.deactivate', $ana))->assertRedirect();

        $this->actingAs($ana->fresh())->get('/crm')->assertRedirect(route('login'));

        auth()->logout();
        $this->post(route('login'), ['email' => $ana->email, 'password' => 'Secreta123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_perfil_de_administrador_no_se_toca(): void
    {
        $admin = $this->perfil('Administrador');

        $this->actingAs($this->admin)->put(route('team.role.update', $admin), ['nombre' => 'X', 'crm_alcance' => 'todos'])->assertForbidden();
        $this->actingAs($this->admin)->delete(route('team.role.destroy', $admin))->assertForbidden();
    }

    public function test_un_perfil_con_gente_no_se_borra(): void
    {
        $this->miembro('Vendedor');

        $this->actingAs($this->admin)->delete(route('team.role.destroy', $this->perfil('Vendedor')))->assertSessionHasErrors('perfil');
        $this->assertNotNull($this->perfil('Vendedor'));
    }

    /* -------------------- Aislamiento entre empresas -------------------- */

    public function test_no_acepta_una_etapa_de_otra_empresa(): void
    {
        $etapaAjena = CrmStage::paraOrganizacion(4)->first();

        $this->actingAs($this->admin)->post(route('crm.leads.store'), [
            'nombre' => 'X', 'origen' => 'prospeccion', 'probabilidad' => 10, 'stage_id' => $etapaAjena->id,
        ])->assertSessionHasErrors('stage_id');
    }

    public function test_el_punto_de_venta_no_vende_productos_de_otra_empresa(): void
    {
        $varianteAjena = ProductVariant::whereHas('product', fn ($q) => $q->where('organization_id', 4))->firstOrFail();

        $this->actingAs($this->admin)->postJson(route('pos.add'), ['variant_id' => $varianteAjena->id, 'quantity' => 1])
            ->assertNotFound();
    }
}
