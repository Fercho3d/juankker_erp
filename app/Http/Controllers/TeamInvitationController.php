<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Quien recibió una invitación al equipo crea aquí su acceso. */
class TeamInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitacion = TeamInvitation::with('organization', 'role')->where('token', $token)->firstOrFail();

        if ($error = $this->motivoInvalida($invitacion)) {
            return redirect()->route('login')->withErrors(['email' => $error]);
        }

        return view('auth.unirse', ['invitacion' => $invitacion]);
    }

    public function accept(Request $request, string $token)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Con la fila bloqueada: dos clics o dos pestañas no crean dos cuentas.
        $user = DB::transaction(function () use ($token, $datos) {
            $invitacion = TeamInvitation::where('token', $token)->lockForUpdate()->firstOrFail();

            if ($error = $this->motivoInvalida($invitacion)) {
                return $error;
            }

            $user = User::create([
                'name' => $datos['name'],
                'email' => $invitacion->email,
                'password' => Hash::make($datos['password']),
                'organization_id' => $invitacion->organization_id,
                'role_id' => $invitacion->role_id,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();   // llegó desde su propio correo
            $invitacion->update(['accepted_at' => now()]);

            return $user;
        });

        if (is_string($user)) {
            return redirect()->route('login')->withErrors(['email' => $user]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->inicio())->with('status', __('¡Bienvenido al equipo de :empresa!', ['empresa' => $user->organization->name]));
    }

    private function motivoInvalida(TeamInvitation $invitacion): ?string
    {
        if (! $invitacion->vigente()) {
            return __('Esta invitación ya no es válida. Pide que te envíen una nueva.');
        }

        if (User::where('email', $invitacion->email)->exists()) {
            return __('Ese correo ya tiene una cuenta. Inicia sesión.');
        }

        return null;
    }
}
