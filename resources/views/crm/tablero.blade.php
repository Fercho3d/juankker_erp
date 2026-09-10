@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-[1600px] mx-auto px-6 py-8">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Embudo de ventas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Arrastra una tarjeta para cambiarla de etapa.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.pendientes') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">Pendientes de hoy</a>
            <a href="{{ route('crm.leads.importar.form') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">Alta masiva</a>
            <a href="{{ route('crm.papelera') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">Papelera <span class="text-gray-400">{{ number_format($enPapelera) }}</span></a>
            <a href="{{ route('crm.leads.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 no-underline">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo prospecto
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif

    {{-- Cifras del embudo --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-gray-200 border border-gray-200 rounded-xl overflow-hidden mb-6">
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">En la mesa</span>
            <span class="block text-xl font-bold text-gray-900 tabular-nums mt-1" id="kpi-mesa">${{ number_format($resumen['valor_en_mesa'], 0) }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Ponderado</span>
            <span class="block text-xl font-bold text-indigo-600 tabular-nums mt-1" id="kpi-pond">${{ number_format($resumen['valor_ponderado'], 0) }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Mensualidad en juego</span>
            <span class="block text-xl font-bold text-emerald-600 tabular-nums mt-1">${{ number_format($resumen['mrr_en_mesa'], 0) }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Vencidos</span>
            <span class="block text-xl font-bold {{ $resumen['vencidos'] > 0 ? 'text-rose-600' : 'text-gray-900' }} tabular-nums mt-1">{{ $resumen['vencidos'] }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Ganados del mes</span>
            <span class="block text-xl font-bold text-gray-900 tabular-nums mt-1">{{ $resumen['ganados_mes'] }}<span class="text-sm font-medium text-gray-400 ml-1.5">{{ $resumen['tasa_cierre_mes'] !== null ? $resumen['tasa_cierre_mes'].'%' : '' }}</span></span>
        </div>
    </div>

    {{-- Filtros: los selectores se aplican al cambiar; la búsqueda, con Enter --}}
    <form method="GET" class="flex flex-wrap items-center gap-2.5 mb-2">
        <input type="text" name="search" value="{{ $filtros['search'] ?? '' }}" placeholder="Buscar empresa, giro, teléfono…"
               class="w-full sm:w-64 px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
        <select name="sector" onchange="this.form.submit()" class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            <option value="">Todos los sectores</option>
            @foreach ($opciones['sectores'] as $valor => $n)
                <option value="{{ $valor }}" @selected(($filtros['sector'] ?? '') === $valor)>{{ $valor }} ({{ number_format($n) }})</option>
            @endforeach
        </select>
        <select name="tamano" onchange="this.form.submit()" class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            <option value="">Cualquier tamaño</option>
            @foreach (\App\Models\Lead::TAMANOS as $min => $etiqueta)
                @if ($n = $opciones['tamanos'][$min] ?? 0)
                    <option value="{{ $min }}" @selected(($filtros['tamano'] ?? '') === (string) $min)>{{ $etiqueta }} personas ({{ number_format($n) }})</option>
                @endif
            @endforeach
        </select>
        <select name="municipio" onchange="this.form.submit()" class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            <option value="">Todos los municipios</option>
            @foreach ($opciones['municipios'] as $valor => $n)
                <option value="{{ $valor }}" @selected(($filtros['municipio'] ?? '') === $valor)>{{ $valor }} ({{ number_format($n) }})</option>
            @endforeach
        </select>
        <select name="contacto" onchange="this.form.submit()" class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            <option value="">Con o sin contacto</option>
            <option value="telefono" @selected(($filtros['contacto'] ?? '') === 'telefono')>Con teléfono</option>
            <option value="email" @selected(($filtros['contacto'] ?? '') === 'email')>Con correo</option>
        </select>
        @if ($filtros)
            <a href="{{ route('crm.tablero') }}" class="px-3 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-900 no-underline">Limpiar filtros</a>
        @endif
    </form>
    <p class="text-xs text-gray-500 mb-5">
        {{ number_format($leadsPorEtapa->sum(fn ($g) => $g->count())) }} prospectos{{ $filtros ? ' con estos filtros' : '' }}.
        Los más grandes van primero; cada columna muestra hasta {{ $tope }}.
    </p>

    {{-- Tablero --}}
    <div class="flex gap-4 overflow-x-auto pb-4 items-start">
        @foreach ($etapas as $etapa)
            @php
                $items = $leadsPorEtapa[$etapa->id] ?? collect();
                $total = $items->sum('valor_estimado');
            @endphp
            <div class="flex-shrink-0 w-[290px] bg-gray-100 rounded-xl border border-gray-200 flex flex-col max-h-[calc(100vh-330px)]"
                 data-etapa="{{ $etapa->id }}"
                 ondragover="event.preventDefault(); this.classList.add('ring-2','ring-indigo-400')"
                 ondragleave="this.classList.remove('ring-2','ring-indigo-400')"
                 ondrop="soltar(event, {{ $etapa->id }}, this)">

                <div class="px-3.5 py-3 border-b border-gray-200 flex items-baseline justify-between gap-2">
                    <span class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $etapa->es_ganada ? 'bg-emerald-500' : ($etapa->es_perdida ? 'bg-rose-400' : 'bg-indigo-400') }}"></span>
                        {{ $etapa->nombre }}
                        <span class="text-gray-400 font-medium">{{ $items->count() }}</span>
                    </span>
                    <span class="text-xs font-semibold text-gray-500 tabular-nums">${{ number_format($total, 0) }}</span>
                </div>

                <div class="p-2.5 flex flex-col gap-2 overflow-y-auto">
                    @forelse ($items->take($tope) as $lead)
                        <a href="{{ route('crm.leads.show', $lead) }}"
                           draggable="true" ondragstart="event.dataTransfer.setData('text/plain', '{{ $lead->id }}')"
                           class="block bg-white rounded-lg border border-gray-200 p-3 no-underline hover:border-indigo-300 hover:shadow-sm transition cursor-grab active:cursor-grabbing">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-900 leading-snug">{{ $lead->empresa ?: $lead->nombre }}</span>
                                @if ($lead->estaVencido())
                                    <span class="shrink-0 w-2 h-2 rounded-full bg-rose-500 mt-1.5" title="Seguimiento vencido"></span>
                                @endif
                            </div>
                            @if ($lead->empresa && $lead->nombre !== $lead->empresa)
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $lead->nombre }}</span>
                            @endif
                            @if ($lead->telefono)
                                <span class="block text-xs text-gray-700 tabular-nums mt-1">{{ $lead->telefono }}</span>
                            @endif
                            @if ($lead->sector || $lead->personal_min || $lead->municipio)
                                <span class="block text-[11px] text-gray-500 mt-1 truncate">
                                    {{ collect([$lead->sector, $lead->rangoPersonal() ? $lead->rangoPersonal().' pers.' : null, $lead->municipio])->filter()->implode(' · ') }}
                                </span>
                            @endif

                            <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                                @if ($lead->valor_estimado > 0)
                                    <span class="text-xs font-bold text-gray-900 tabular-nums">${{ number_format($lead->valor_estimado, 0) }}</span>
                                @endif
                                @if ($lead->valor_mensual > 0)
                                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">+${{ number_format($lead->valor_mensual, 0) }}/mes</span>
                                @endif
                                <span class="text-[11px] text-gray-400 tabular-nums ml-auto">{{ $lead->probabilidad }}%</span>
                            </div>

                            @if ($lead->proxima_accion_at)
                                <div class="mt-2 pt-2 border-t border-gray-100 text-[11px] {{ $lead->estaVencido() ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                                    {{ $lead->proxima_accion ?: 'Seguimiento' }} · {{ $lead->proxima_accion_at->format('d/m') }}
                                </div>
                            @elseif ($etapa->esAbierta())
                                <div class="mt-2 pt-2 border-t border-gray-100 text-[11px] text-amber-600 font-medium">Sin próxima acción</div>
                            @endif
                        </a>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-6 m-0">Vacío</p>
                    @endforelse
                    @if ($items->count() > $tope)
                        <p class="text-[11px] text-gray-500 text-center py-2 m-0">y {{ number_format($items->count() - $tope) }} más — afina los filtros</p>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Papelera: soltar una tarjeta aquí la descarta con el motivo elegido --}}
        <div class="flex-shrink-0 w-[230px] rounded-xl border-2 border-dashed border-gray-300 p-4 flex flex-col items-center text-center gap-2 transition"
             ondragover="event.preventDefault(); this.classList.add('border-rose-400','bg-rose-50')"
             ondragleave="this.classList.remove('border-rose-400','bg-rose-50')"
             ondrop="descartar(event, this)">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" class="text-gray-400" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>
            <span class="text-sm font-semibold text-gray-800">Papelera</span>
            <span class="text-xs text-gray-500">Arrastra aquí a quien no es prospecto. Se puede restaurar.</span>
            <select id="motivo-descarte" class="w-full mt-1 px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500" aria-label="Motivo al soltar">
                @foreach (\App\Models\Lead::MOTIVOS_DESCARTE as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <a href="{{ route('crm.papelera') }}" class="text-xs font-semibold text-indigo-600 no-underline hover:underline mt-1">Ver descartados ({{ number_format($enPapelera) }})</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function soltar(evento, etapaId, columna) {
    evento.preventDefault();
    columna.classList.remove('ring-2', 'ring-indigo-400');

    const leadId = evento.dataTransfer.getData('text/plain');
    if (!leadId) return;

    fetch(`/crm/leads/${leadId}/mover`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ stage_id: etapaId }),
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => window.location.reload())
    .catch(() => alert('No se pudo mover el prospecto. Recarga la página e inténtalo de nuevo.'));
}

function descartar(evento, zona) {
    evento.preventDefault();
    zona.classList.remove('border-rose-400', 'bg-rose-50');

    const leadId = evento.dataTransfer.getData('text/plain');
    if (!leadId) return;

    fetch(`/crm/leads/${leadId}/descartar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ motivo: document.getElementById('motivo-descarte').value }),
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => window.location.reload())
    .catch(() => alert('No se pudo descartar el prospecto. Recarga la página e inténtalo de nuevo.'));
}
</script>
@endpush
@endsection
