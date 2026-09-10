<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CrmController;
use App\Models\CrmActivity;
use App\Models\CrmStage;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * API del CRM sobre tokens de Sanctum.
 *
 * Pensada para operar el embudo desde fuera del navegador: dar de alta
 * prospectos en lote, consultar qué hay pendiente y registrar seguimiento
 * sin abrir la aplicación. Todo queda acotado a la organización del token.
 */
class CrmApiController extends Controller
{
    private function orgId(Request $request): int
    {
        return (int) $request->user()->organization_id;
    }

    private function buscar(Request $request, int $id): Lead
    {
        return Lead::visiblesPara($request->user())->findOrFail($id);
    }

    /**
     * Cifras del embudo: qué hay en la mesa, qué está vencido, cómo va el mes.
     */
    public function resumen(Request $request): JsonResponse
    {
        return response()->json(CrmController::resumen($request->user()));
    }

    public function etapas(Request $request): JsonResponse
    {
        return response()->json(
            CrmStage::paraOrganizacion($this->orgId($request))
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'orden' => $e->orden,
                    'es_ganada' => $e->es_ganada,
                    'es_perdida' => $e->es_perdida,
                ])
        );
    }

    /**
     * Lista de leads. Filtros: search, stage_id, abiertos=1, pendientes=1.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lead::visiblesPara($request->user())
            ->with('stage')
            ->search($request->input('search'));

        if ($request->filled('stage_id')) {
            $query->where('stage_id', $request->integer('stage_id'));
        }

        if ($request->boolean('abiertos')) {
            $query->abiertos();
        }

        if ($request->boolean('pendientes')) {
            $query->pendientes();
        }

        $leads = $query->orderByDesc('valor_estimado')
            ->paginate(min($request->integer('per_page', 50), 200));

        return response()->json([
            'data' => collect($leads->items())->map(fn ($l) => $this->serializar($l)),
            'total' => $leads->total(),
            'pagina' => $leads->currentPage(),
            'ultima_pagina' => $leads->lastPage(),
        ]);
    }

    public function show(Request $request, int $lead): JsonResponse
    {
        $modelo = $this->buscar($request, $lead);

        return response()->json(
            $this->serializar($modelo->load('stage')) + [
                'notas' => $modelo->notas,
                'actividades' => $modelo->activities()->with('user')->limit(50)->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'tipo' => $a->tipo,
                        'descripcion' => $a->descripcion,
                        'programada_at' => $a->programada_at?->toDateTimeString(),
                        'completada_at' => $a->completada_at?->toDateTimeString(),
                        'usuario' => $a->user?->name,
                    ]),
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = $this->orgId($request);
        $validated = $request->validate($this->reglas());

        $validated['organization_id'] = $orgId;
        $validated['owner_id'] = $request->user()->id;
        $validated['stage_id'] = $validated['stage_id']
            ?? CrmStage::paraOrganizacion($orgId)->first()->id;

        $lead = Lead::create($validated);

        return response()->json($this->serializar($lead->load('stage')), 201);
    }

    public function update(Request $request, int $lead): JsonResponse
    {
        $modelo = $this->buscar($request, $lead);
        $modelo->update($request->validate($this->reglas(false)));

        return response()->json($this->serializar($modelo->fresh()->load('stage')));
    }

    /**
     * Alta en lote. Espera {"leads": [{empresa, nombre, telefono, email, ...}, ...]}
     * y omite lo que ya exista por empresa, teléfono o correo.
     */
    public function importar(Request $request): JsonResponse
    {
        $orgId = $this->orgId($request);

        $validated = $request->validate([
            'leads' => 'required|array|min:1|max:500',
            'leads.*.empresa' => 'required|string|max:255',
            'leads.*.nombre' => 'nullable|string|max:255',
            'leads.*.email' => 'nullable|email|max:255',
            'leads.*.telefono' => 'nullable|string|max:30',
            'leads.*.origen' => 'nullable|string|max:50',
            'leads.*.valor_estimado' => 'nullable|numeric|min:0',
            'leads.*.valor_mensual' => 'nullable|numeric|min:0',
            'leads.*.notas' => 'nullable|string',
            'stage_id' => ['nullable', 'integer', Rule::exists('crm_stages', 'id')->where('organization_id', $request->user()->organization_id)],
        ]);

        $etapa = $validated['stage_id'] ?? CrmStage::paraOrganizacion($orgId)->first()->id;
        $creados = [];
        $omitidos = [];

        DB::transaction(function () use ($validated, $orgId, $etapa, $request, &$creados, &$omitidos) {
            foreach ($validated['leads'] as $fila) {
                $duplicado = Lead::withTrashed()->deOrganizacion($orgId)
                    ->where(function ($q) use ($fila) {
                        $q->where('empresa', $fila['empresa']);
                        if (! empty($fila['telefono'])) {
                            $q->orWhere('telefono', $fila['telefono']);
                        }
                        if (! empty($fila['email'])) {
                            $q->orWhere('email', $fila['email']);
                        }
                    })
                    ->first();

                if ($duplicado) {
                    $omitidos[] = ['empresa' => $fila['empresa'], 'lead_id' => $duplicado->id];
                    continue;
                }

                $lead = Lead::create([
                    'organization_id' => $orgId,
                    'stage_id' => $etapa,
                    'owner_id' => $request->user()->id,
                    'nombre' => $fila['nombre'] ?? $fila['empresa'],
                    'empresa' => $fila['empresa'],
                    'email' => $fila['email'] ?? null,
                    'telefono' => $fila['telefono'] ?? null,
                    'origen' => $fila['origen'] ?? 'prospeccion',
                    'valor_estimado' => $fila['valor_estimado'] ?? 0,
                    'valor_mensual' => $fila['valor_mensual'] ?? 0,
                    'probabilidad' => 10,
                    'notas' => $fila['notas'] ?? null,
                ]);

                $creados[] = ['id' => $lead->id, 'empresa' => $lead->empresa];
            }
        });

        return response()->json([
            'creados' => count($creados),
            'omitidos' => count($omitidos),
            'leads' => $creados,
            'duplicados' => $omitidos,
        ], 201);
    }

    public function mover(Request $request, int $lead): JsonResponse
    {
        $modelo = $this->buscar($request, $lead);

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:crm_stages,id',
        ]);

        $etapa = CrmStage::where('organization_id', $this->orgId($request))
            ->findOrFail($validated['stage_id']);

        CrmController::aplicarEtapa($modelo, $etapa, $modelo->stage->nombre ?? '—');

        return response()->json($this->serializar($modelo->fresh()->load('stage')));
    }

    public function actividad(Request $request, int $lead): JsonResponse
    {
        $modelo = $this->buscar($request, $lead);

        $validated = $request->validate([
            'tipo' => 'required|in:llamada,whatsapp,email,visita,nota',
            'descripcion' => 'required|string|max:5000',
            'completada' => 'nullable|boolean',
            'proxima_accion' => 'nullable|string|max:255',
            'proxima_accion_at' => 'nullable|date',
        ]);

        $completada = $request->boolean('completada', true);

        $actividad = CrmActivity::create([
            'organization_id' => $modelo->organization_id,
            'lead_id' => $modelo->id,
            'user_id' => $request->user()->id,
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'completada_at' => $completada ? now() : null,
        ]);

        $cambios = [];
        if ($completada && $validated['tipo'] !== 'nota') {
            $cambios['ultimo_contacto_at'] = now();
        }
        if (! empty($validated['proxima_accion_at'])) {
            $cambios['proxima_accion_at'] = $validated['proxima_accion_at'];
            $cambios['proxima_accion'] = $validated['proxima_accion'] ?? 'Dar seguimiento';
        }
        if ($cambios) {
            $modelo->update($cambios);
        }

        return response()->json([
            'actividad_id' => $actividad->id,
            'lead' => $this->serializar($modelo->fresh()->load('stage')),
        ], 201);
    }

    /**
     * Lo que hay que atender hoy: acciones vencidas, de hoy, y leads sin seguimiento.
     */
    public function pendientes(Request $request): JsonResponse
    {
        $orgId = $this->orgId($request);

        $leads = Lead::visiblesPara($request->user())->pendientes()->with('stage')
            ->orderBy('proxima_accion_at')->get();

        return response()->json([
            'vencidos' => $leads->filter(fn ($l) => $l->estaVencido())
                ->map(fn ($l) => $this->serializar($l))->values(),
            'hoy' => $leads->filter(fn ($l) => $l->proxima_accion_at?->isToday())
                ->map(fn ($l) => $this->serializar($l))->values(),
            'sin_proxima_accion' => Lead::visiblesPara($request->user())->abiertos()
                ->whereNull('proxima_accion_at')->with('stage')->get()
                ->map(fn ($l) => $this->serializar($l))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'nombre' => $lead->nombre,
            'empresa' => $lead->empresa,
            'email' => $lead->email,
            'telefono' => $lead->telefono,
            'etapa' => $lead->stage?->nombre,
            'stage_id' => $lead->stage_id,
            'origen' => $lead->origen,
            'valor_estimado' => (float) $lead->valor_estimado,
            'valor_mensual' => (float) $lead->valor_mensual,
            'probabilidad' => $lead->probabilidad,
            'valor_ponderado' => $lead->valorPonderado(),
            'proxima_accion' => $lead->proxima_accion,
            'proxima_accion_at' => $lead->proxima_accion_at?->toDateTimeString(),
            'vencido' => $lead->estaVencido(),
            'dias_sin_contacto' => $lead->diasSinContacto(),
            'client_id' => $lead->client_id,
            'cerrado_at' => $lead->cerrado_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(bool $crear = true): array
    {
        $req = $crear ? 'required' : 'sometimes|required';

        return [
            'nombre' => "{$req}|string|max:255",
            'empresa' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:30',
            'stage_id' => ['nullable', 'integer', Rule::exists('crm_stages', 'id')->where('organization_id', auth()->user()->organization_id)],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('organization_id', auth()->user()->organization_id)],
            'origen' => 'nullable|string|max:50',
            'valor_estimado' => 'nullable|numeric|min:0|max:99999999',
            'valor_mensual' => 'nullable|numeric|min:0|max:99999999',
            'probabilidad' => 'nullable|integer|min:0|max:100',
            'proxima_accion' => 'nullable|string|max:255',
            'proxima_accion_at' => 'nullable|date',
            'motivo_perdida' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
        ];
    }
}
