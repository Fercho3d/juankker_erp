<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\CrmStage;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmController extends Controller
{
    /** Más tarjetas que esto por columna ya no se trabajan: se afinan los filtros. */
    private const TOPE_POR_LISTA = 50;

    /**
     * Pendientes de hoy: la primera pantalla que se abre en la mañana.
     */
    public function pendientes()
    {
        $orgId = Auth::user()->organization_id;

        $actividades = CrmActivity::deOrganizacion($orgId)
            ->pendientes()
            ->where('programada_at', '<=', now()->endOfDay())
            ->with('lead')
            ->orderBy('programada_at')
            ->get();

        $leads = Lead::deOrganizacion($orgId)
            ->pendientes()
            ->with('stage')
            ->orderBy('proxima_accion_at')
            ->get();

        $sinSeguimiento = Lead::deOrganizacion($orgId)->abiertos()->whereNull('proxima_accion_at');

        return view('crm.pendientes', [
            'actividades' => $actividades,
            'leads' => $leads,
            'sinSeguimiento' => (clone $sinSeguimiento)->with('stage')
                ->orderByDesc('personal_min')->orderBy('updated_at')
                ->limit(self::TOPE_POR_LISTA)->get(),
            'totalSinSeguimiento' => $sinSeguimiento->count(),
            'resumen' => self::resumen($orgId),
        ]);
    }

    /**
     * Tablero Kanban del embudo.
     */
    public function tablero(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $filtros = array_filter($request->only(['search', 'sector', 'tamano', 'municipio', 'contacto']));

        $leads = Lead::deOrganizacion($orgId)
            ->filtrar($filtros)
            ->orderBy('orden')
            ->orderByDesc('valor_estimado')
            ->orderByDesc('personal_min')
            ->get()
            ->groupBy('stage_id');

        return view('crm.tablero', [
            'etapas' => CrmStage::paraOrganizacion($orgId),
            'leadsPorEtapa' => $leads,
            'filtros' => $filtros,
            'opciones' => $this->opcionesDeFiltro($orgId),
            'tope' => self::TOPE_POR_LISTA,
            'resumen' => self::resumen($orgId),
        ]);
    }

    /**
     * Valores que existen de verdad en el embudo, con cuántos leads tiene cada uno.
     *
     * @return array<string, \Illuminate\Support\Collection<string, int>>
     */
    private function opcionesDeFiltro(int $orgId): array
    {
        $conteo = fn (string $campo) => Lead::deOrganizacion($orgId)->whereNotNull($campo)
            ->selectRaw("{$campo} as valor, count(*) as n")->groupBy($campo)
            ->orderByDesc('n')->pluck('n', 'valor');

        return ['sectores' => $conteo('sector'), 'municipios' => $conteo('municipio')];
    }

    /**
     * Mueve un lead de etapa (drag & drop del tablero).
     */
    public function mover(Request $request, Lead $lead)
    {
        $this->autorizar($lead);

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:crm_stages,id',
        ]);

        $etapa = CrmStage::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($validated['stage_id']);

        $anterior = $lead->stage->nombre ?? '—';
        self::aplicarEtapa($lead, $etapa, $anterior);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'lead' => $lead->fresh()->load('stage'),
                'resumen' => self::resumen(Auth::user()->organization_id),
            ]);
        }

        return back()->with('status', 'lead-movido');
    }

    /**
     * Cambia la etapa de un lead y registra el movimiento en la bitácora.
     * Compartido entre el tablero web y la API.
     */
    public static function aplicarEtapa(Lead $lead, CrmStage $etapa, string $etapaAnterior): void
    {
        $lead->stage_id = $etapa->id;
        $lead->cerrado_at = $etapa->esAbierta() ? null : now();

        if ($etapa->es_ganada) {
            $lead->probabilidad = 100;
            $lead->proxima_accion = null;
            $lead->proxima_accion_at = null;
        } elseif ($etapa->es_perdida) {
            $lead->probabilidad = 0;
            $lead->proxima_accion = null;
            $lead->proxima_accion_at = null;
        }

        $lead->save();

        CrmActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => Auth::id(),
            'tipo' => 'etapa',
            'descripcion' => "De «{$etapaAnterior}» a «{$etapa->nombre}»",
            'completada_at' => now(),
        ]);
    }

    /**
     * Cifras del embudo. Las consume el tablero, los pendientes y la API.
     *
     * @return array<string, mixed>
     */
    public static function resumen(int $orgId): array
    {
        $abiertos = Lead::deOrganizacion($orgId)->abiertos()->get();

        $inicioMes = now()->startOfMonth();
        $ganadosMes = Lead::deOrganizacion($orgId)
            ->whereHas('stage', fn ($q) => $q->where('es_ganada', true))
            ->where('cerrado_at', '>=', $inicioMes)
            ->get();

        $perdidosMes = Lead::deOrganizacion($orgId)
            ->whereHas('stage', fn ($q) => $q->where('es_perdida', true))
            ->where('cerrado_at', '>=', $inicioMes)
            ->count();

        $cerradosMes = $ganadosMes->count() + $perdidosMes;

        return [
            'leads_abiertos' => $abiertos->count(),
            'valor_en_mesa' => round((float) $abiertos->sum('valor_estimado'), 2),
            'valor_ponderado' => round($abiertos->sum(fn ($l) => $l->valorPonderado()), 2),
            'mrr_en_mesa' => round((float) $abiertos->sum('valor_mensual'), 2),
            'vencidos' => $abiertos->filter(fn ($l) => $l->estaVencido())->count(),
            'sin_proxima_accion' => $abiertos->whereNull('proxima_accion_at')->count(),
            'ganados_mes' => $ganadosMes->count(),
            'ganado_mes_monto' => round((float) $ganadosMes->sum('valor_estimado'), 2),
            'mrr_ganado_mes' => round((float) $ganadosMes->sum('valor_mensual'), 2),
            'tasa_cierre_mes' => $cerradosMes > 0
                ? round($ganadosMes->count() / $cerradosMes * 100, 1)
                : null,
        ];
    }

    private function autorizar(Lead $lead): void
    {
        if ($lead->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
