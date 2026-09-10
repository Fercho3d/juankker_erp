<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmActivity;
use App\Models\CrmStage;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function create()
    {
        $orgId = Auth::user()->organization_id;

        return view('crm.form', [
            'lead' => new Lead(['probabilidad' => 20, 'origen' => 'prospeccion']),
            'etapas' => CrmStage::paraOrganizacion($orgId),
            'origenes' => Lead::ORIGENES,
        ]);
    }

    public function store(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $validated = $this->validar($request);

        $validated['organization_id'] = $orgId;
        $validated['owner_id'] = Auth::id();
        $validated['stage_id'] = $validated['stage_id']
            ?? CrmStage::paraOrganizacion($orgId)->first()->id;

        $lead = Lead::create($validated);

        return redirect()->route('crm.leads.show', $lead)->with('status', 'lead-creado');
    }

    public function show(Lead $lead)
    {
        $this->autorizar($lead);

        return view('crm.show', [
            'lead' => $lead->load(['stage', 'client', 'owner']),
            'actividades' => $lead->activities()->with('user')->get(),
            'etapas' => CrmStage::paraOrganizacion($lead->organization_id),
            'tipos' => CrmActivity::TIPOS,
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->autorizar($lead);

        return view('crm.form', [
            'lead' => $lead,
            'etapas' => CrmStage::paraOrganizacion($lead->organization_id),
            'origenes' => Lead::ORIGENES,
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->autorizar($lead);
        $lead->update($this->validar($request));

        return redirect()->route('crm.leads.show', $lead)->with('status', 'lead-actualizado');
    }

    public function destroy(Lead $lead)
    {
        $this->autorizar($lead);
        $lead->delete();

        return redirect()->route('crm.tablero')->with('status', 'lead-eliminado');
    }

    /**
     * Convierte un lead ganado en cliente del ERP, conservando el enlace.
     */
    public function convertir(Request $request, Lead $lead)
    {
        $this->autorizar($lead);

        if ($lead->client_id) {
            return redirect()->route('clientes.edit', $lead->client_id);
        }

        $validated = $request->validate([
            'rfc' => 'required|string|min:12|max:13',
            'razon_social' => 'required|string|max:255',
        ]);

        $client = Client::create([
            'organization_id' => $lead->organization_id,
            'tipo_persona' => strlen($validated['rfc']) === 13 ? 'Física' : 'Moral',
            'razon_social' => $validated['razon_social'],
            'rfc' => strtoupper($validated['rfc']),
            'regimen_fiscal' => '616 - Sin obligaciones fiscales',
            'uso_cfdi' => 'G03 - Gastos en general',
            'email' => $lead->email ?: 'sincorreo@ejemplo.com',
            'telefono' => $lead->telefono,
            'calle' => 'Por definir',
            'num_exterior' => 'S/N',
            'colonia' => 'Por definir',
            'codigo_postal' => '00000',
            'ciudad' => 'Por definir',
            'estado' => 'Por definir',
            'notas' => "Convertido desde el lead #{$lead->id}.",
        ]);

        $lead->update(['client_id' => $client->id]);

        return redirect()->route('clientes.edit', $client)
            ->with('status', 'lead-convertido');
    }

    /* -------------------- Alta masiva -------------------- */

    public function importarForm()
    {
        return view('crm.importar', [
            'etapas' => CrmStage::paraOrganizacion(Auth::user()->organization_id),
        ]);
    }

    /**
     * Alta masiva pegando líneas separadas por tabulador o coma:
     * empresa, contacto, telefono, email, origen
     */
    public function importar(Request $request)
    {
        $orgId = Auth::user()->organization_id;

        $validated = $request->validate([
            'datos' => 'required|string|max:200000',
            'stage_id' => 'nullable|integer|exists:crm_stages,id',
            'origen' => 'nullable|string|max:50',
        ]);

        $etapa = $validated['stage_id']
            ?? CrmStage::paraOrganizacion($orgId)->first()->id;

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($validated, $orgId, $etapa, &$creados, &$omitidos) {
            foreach (preg_split('/\r\n|\r|\n/', $validated['datos']) as $linea) {
                $linea = trim($linea);
                if ($linea === '') {
                    continue;
                }

                $campos = array_map('trim', preg_split('/\t|,(?=(?:[^"]*"[^"]*")*[^"]*$)/', $linea));
                $campos = array_map(fn ($c) => trim($c, "\" "), $campos);

                $empresa = $campos[0] ?? '';
                if ($empresa === '') {
                    $omitidos++;
                    continue;
                }

                $telefono = $campos[2] ?? null;
                $email = $campos[3] ?? null;

                // Evita duplicados por teléfono o correo dentro de la organización.
                $duplicado = Lead::deOrganizacion($orgId)
                    ->where(function ($q) use ($telefono, $email, $empresa) {
                        $q->where('empresa', $empresa);
                        if ($telefono) {
                            $q->orWhere('telefono', $telefono);
                        }
                        if ($email) {
                            $q->orWhere('email', $email);
                        }
                    })
                    ->exists();

                if ($duplicado) {
                    $omitidos++;
                    continue;
                }

                Lead::create([
                    'organization_id' => $orgId,
                    'stage_id' => $etapa,
                    'owner_id' => Auth::id(),
                    'nombre' => $campos[1] ?: $empresa,
                    'empresa' => $empresa,
                    'telefono' => $telefono ?: null,
                    'email' => $email ?: null,
                    'origen' => $campos[4] ?? ($validated['origen'] ?? 'prospeccion'),
                    'probabilidad' => 10,
                ]);

                $creados++;
            }
        });

        return redirect()->route('crm.tablero')
            ->with('status', "Se dieron de alta {$creados} prospectos. Omitidos por duplicado o vacíos: {$omitidos}.");
    }

    /* -------------------- Internos -------------------- */

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:30',
            'stage_id' => 'nullable|integer|exists:crm_stages,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'origen' => 'required|string|max:50',
            'valor_estimado' => 'nullable|numeric|min:0|max:99999999',
            'valor_mensual' => 'nullable|numeric|min:0|max:99999999',
            'probabilidad' => 'required|integer|min:0|max:100',
            'proxima_accion' => 'nullable|string|max:255',
            'proxima_accion_at' => 'nullable|date',
            'motivo_perdida' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
        ]);
    }

    private function autorizar(Lead $lead): void
    {
        if ($lead->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
