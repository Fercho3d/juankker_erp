<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmActivityController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $this->autorizar($lead);

        $validated = $request->validate([
            'tipo' => 'required|in:llamada,whatsapp,email,visita,nota',
            'descripcion' => 'required|string|max:5000',
            'programada_at' => 'nullable|date',
            'completada' => 'nullable|boolean',
            'proxima_accion' => 'nullable|string|max:255',
            'proxima_accion_at' => 'nullable|date',
        ]);

        $completada = $request->boolean('completada', true);

        CrmActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => Auth::id(),
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'programada_at' => $validated['programada_at'] ?? null,
            'completada_at' => $completada ? now() : null,
        ]);

        // Registrar contacto y reagendar el seguimiento en el mismo movimiento:
        // un lead nunca debe quedarse sin próxima acción definida.
        $cambios = [];

        if ($completada && $validated['tipo'] !== 'nota') {
            $cambios['ultimo_contacto_at'] = now();
        }

        if (! empty($validated['proxima_accion_at'])) {
            $cambios['proxima_accion_at'] = $validated['proxima_accion_at'];
            $cambios['proxima_accion'] = $validated['proxima_accion'] ?? 'Dar seguimiento';
        }

        if ($cambios) {
            $lead->update($cambios);
        }

        return back()->with('status', 'actividad-registrada');
    }

    public function completar(CrmActivity $actividad)
    {
        if ($actividad->organization_id !== Auth::user()->organization_id || ! $actividad->lead?->visiblePara(Auth::user())) {
            abort(403);
        }

        $actividad->update(['completada_at' => now()]);
        $actividad->lead?->update(['ultimo_contacto_at' => now()]);

        return back()->with('status', 'actividad-completada');
    }

    private function autorizar(Lead $lead): void
    {
        if (! $lead->visiblePara(Auth::user())) {
            abort(403);
        }
    }
}
