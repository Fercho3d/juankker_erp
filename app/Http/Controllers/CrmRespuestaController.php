<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Correos que contestaron los prospectos: la campana de la barra superior.
 */
class CrmRespuestaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $vistasAt = $user->respuestas_vistas_at;
        $user->forceFill(['respuestas_vistas_at' => now()])->save();

        return view('crm.respuestas', [
            'respuestas' => self::respuestas($user)->with('lead')->latest()->limit(50)->get(),
            'vistasAt' => $vistasAt,
        ]);
    }

    /** Lo que consulta la campana cada minuto. */
    public function nuevas(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'nuevas' => self::respuestas($user)
                ->when($user->respuestas_vistas_at, fn ($q, $desde) => $q->where('created_at', '>', $desde))
                ->count(),
            'ultima' => (int) self::respuestas($user)->max('id'),
        ]);
    }

    private static function respuestas(User $user)
    {
        return CrmActivity::deOrganizacion($user->organization_id)
            ->whereNotNull('mensaje_id')
            ->whereHas('lead', fn ($q) => $q->visiblesPara($user));
    }
}
