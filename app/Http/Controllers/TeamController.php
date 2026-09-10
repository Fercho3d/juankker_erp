<?php

namespace App\Http\Controllers;

use App\Mail\InvitacionEquipoMail;
use App\Models\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Equipo de la organización: quién está, con qué perfil, invitaciones
 * pendientes, y los perfiles de acceso que se pueden personalizar.
 */
class TeamController extends Controller
{
    public function index()
    {
        $org = Auth::user()->organization;

        return view('equipo.index', [
            'miembros' => $org->users()->where('is_superadmin', false)->with('role')
                ->orderByDesc('activo')->orderBy('name')->get(),
            'invitaciones' => TeamInvitation::where('organization_id', $org->id)->pendientes()->with('role')->latest()->get(),
            'perfiles' => Role::paraOrganizacion($org->id)->loadCount(['users' => fn ($q) => $q->where('activo', true)]),
            'cupo' => $this->cupo(),
        ]);
    }

    /* -------------------- Invitaciones -------------------- */

    public function invite(Request $request)
    {
        $org = Auth::user()->organization;
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('organization_id', $org->id)],
        ], ['email.unique' => __('Ese correo ya tiene una cuenta en Juankker ERP.')]);

        if (TeamInvitation::where('organization_id', $org->id)->where('email', $datos['email'])->pendientes()->exists()) {
            return back()->withErrors(['email' => __('Ya hay una invitación pendiente para ese correo. Puedes reenviarla.')])->withInput();
        }

        if (! $this->hayCupo()) {
            return back()->withErrors(['email' => __('Tu plan permite :n usuarios y ya están ocupados. Cambia de plan para sumar más.', ['n' => $org->max_users])])->withInput();
        }

        $invitacion = (new TeamInvitation($datos + ['organization_id' => $org->id, 'invited_by' => Auth::id()]))->renovar();
        $invitacion->save();

        return back()->with('status', $this->enviar($invitacion)
            ? __('Invitación enviada a :email.', ['email' => $invitacion->email])
            : __('La invitación quedó guardada pero el correo no salió. Reenvíala en un momento.'));
    }

    public function resend(TeamInvitation $invitacion)
    {
        $this->deMiOrganizacion($invitacion->organization_id);
        $invitacion->renovar()->save();

        return back()->with('status', $this->enviar($invitacion)
            ? __('Invitación reenviada a :email.', ['email' => $invitacion->email])
            : __('El correo no salió. Inténtalo de nuevo en un momento.'));
    }

    public function cancelInvitation(TeamInvitation $invitacion)
    {
        $this->deMiOrganizacion($invitacion->organization_id);
        $invitacion->delete();

        return back()->with('status', __('Invitación cancelada.'));
    }

    /* -------------------- Miembros -------------------- */

    public function updateMember(Request $request, User $miembro)
    {
        $this->editable($miembro);
        $datos = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('organization_id', $miembro->organization_id)],
        ]);
        $miembro->update($datos);

        return back()->with('status', __('Perfil de :nombre actualizado.', ['nombre' => $miembro->name]));
    }

    public function deactivate(User $miembro)
    {
        $this->editable($miembro);
        $miembro->update(['activo' => false]);
        $miembro->tokens()->delete();   // también se corta su acceso a la API

        return back()->with('status', __(':nombre ya no tiene acceso. Sus prospectos siguen a su nombre; reasígnalos desde el embudo.', ['nombre' => $miembro->name]));
    }

    public function activate(User $miembro)
    {
        $this->editable($miembro);

        if (! $this->hayCupo()) {
            return back()->withErrors(['miembro' => __('No hay lugar en tu plan para reactivarlo. Cambia de plan o desactiva a alguien.')]);
        }

        $miembro->update(['activo' => true]);

        return back()->with('status', __(':nombre vuelve a tener acceso.', ['nombre' => $miembro->name]));
    }

    /* -------------------- Perfiles -------------------- */

    public function createRole()
    {
        return view('equipo.perfil', ['perfil' => new Role(['crm_alcance' => Role::ALCANCE_PROPIOS, 'permisos' => ['crm']])]);
    }

    public function storeRole(Request $request)
    {
        Role::create($this->validarPerfil($request) + ['organization_id' => Auth::user()->organization_id]);

        return redirect()->route('team.index')->with('status', __('Perfil creado.'));
    }

    public function editRole(Role $perfil)
    {
        $this->perfilEditable($perfil);

        return view('equipo.perfil', ['perfil' => $perfil]);
    }

    public function updateRole(Request $request, Role $perfil)
    {
        $this->perfilEditable($perfil);
        $perfil->update($this->validarPerfil($request, $perfil));

        return redirect()->route('team.index')->with('status', __('Perfil actualizado.'));
    }

    public function destroyRole(Role $perfil)
    {
        $this->perfilEditable($perfil);

        if ($perfil->users()->exists() || TeamInvitation::where('role_id', $perfil->id)->pendientes()->exists()) {
            return back()->withErrors(['perfil' => __('Ese perfil tiene gente o invitaciones. Cámbiales el perfil antes de borrarlo.')]);
        }

        $perfil->delete();

        return redirect()->route('team.index')->with('status', __('Perfil eliminado.'));
    }

    /* -------------------- Ayudantes -------------------- */

    private function validarPerfil(Request $request, ?Role $perfil = null): array
    {
        $org = Auth::user()->organization_id;
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60',
                Rule::unique('roles', 'nombre')->where('organization_id', $org)->ignore($perfil?->id)],
            'permisos' => ['array'],
            'permisos.*' => [Rule::in(array_keys(Role::MODULOS))],
            'crm_alcance' => ['required', Rule::in([Role::ALCANCE_TODOS, Role::ALCANCE_PROPIOS])],
        ]);
        $datos['permisos'] = array_values($datos['permisos'] ?? []);

        return $datos;
    }

    /** Lugares ocupados: miembros activos más invitaciones pendientes. */
    private function cupo(): array
    {
        $org = Auth::user()->organization;

        return [
            'usados' => $org->users()->where('is_superadmin', false)->where('activo', true)->count()
                + TeamInvitation::where('organization_id', $org->id)->pendientes()->count(),
            'maximo' => (int) $org->max_users,
        ];
    }

    private function hayCupo(): bool
    {
        $cupo = $this->cupo();

        return $cupo['usados'] < $cupo['maximo'];
    }

    private function enviar(TeamInvitation $invitacion): bool
    {
        try {
            Mail::to($invitacion->email)->send(new InvitacionEquipoMail($invitacion->load('organization', 'role'), Auth::user()->name));

            return true;
        } catch (\Throwable $e) {
            Log::warning('No salió la invitación al equipo', ['email' => $invitacion->email, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function deMiOrganizacion(?int $organizationId): void
    {
        abort_unless($organizationId === Auth::user()->organization_id, 404);
    }

    /** Nadie se cambia a sí mismo ni toca al dueño: así nadie se queda sin administrador. */
    private function editable(User $miembro): void
    {
        $this->deMiOrganizacion($miembro->organization_id);
        abort_if($miembro->id === Auth::id() || $miembro->esDueno() || $miembro->isSuperadmin(), 403);
    }

    private function perfilEditable(Role $perfil): void
    {
        $this->deMiOrganizacion($perfil->organization_id);
        abort_if($perfil->es_admin, 403, __('El perfil de administrador no se edita.'));
    }
}
