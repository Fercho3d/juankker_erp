@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pendientes de hoy</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->translatedFormat('l d \d\e F') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.tablero') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">Ver embudo</a>
            <a href="{{ route('crm.leads.create') }}" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 no-underline">Nuevo prospecto</a>
        </div>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 rounded-xl overflow-hidden mb-8">
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">En la mesa</span>
            <span class="block text-xl font-bold text-gray-900 tabular-nums mt-1">${{ number_format($resumen['valor_en_mesa'], 0) }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Mensualidad en juego</span>
            <span class="block text-xl font-bold text-emerald-600 tabular-nums mt-1">${{ number_format($resumen['mrr_en_mesa'], 0) }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Prospectos abiertos</span>
            <span class="block text-xl font-bold text-gray-900 tabular-nums mt-1">{{ $resumen['leads_abiertos'] }}</span>
        </div>
        <div class="bg-white px-4 py-3.5">
            <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Ganado este mes</span>
            <span class="block text-xl font-bold text-gray-900 tabular-nums mt-1">${{ number_format($resumen['ganado_mes_monto'], 0) }}</span>
        </div>
    </div>

    {{-- Seguimientos --}}
    <h2 class="text-base font-bold text-gray-900 mb-3">A quién le toca hoy <span class="text-gray-400 font-medium">({{ $leads->count() }})</span></h2>

    @if ($leads->count())
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
            @foreach ($leads as $lead)
                <a href="{{ route('crm.leads.show', $lead) }}"
                   class="flex items-center gap-4 px-4 py-3.5 no-underline hover:bg-gray-50 transition {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <span class="shrink-0 w-2 h-2 rounded-full {{ $lead->estaVencido() ? 'bg-rose-500' : 'bg-amber-400' }}"></span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 truncate">{{ $lead->empresa ?: $lead->nombre }}</span>
                        <span class="block text-xs text-gray-500 truncate">{{ $lead->proxima_accion ?: 'Dar seguimiento' }}</span>
                    </div>
                    <span class="shrink-0 text-xs font-medium {{ $lead->estaVencido() ? 'text-rose-600' : 'text-gray-500' }} tabular-nums">
                        {{ $lead->estaVencido() ? 'Venció '.$lead->proxima_accion_at->format('d/m') : 'Hoy' }}
                    </span>
                    <span class="shrink-0 hidden sm:inline text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">{{ $lead->stage->nombre }}</span>
                    @if ($lead->telefono)
                        <span class="shrink-0 hidden md:inline text-xs text-gray-400 tabular-nums">{{ $lead->telefono }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-8 text-center mb-8">
            <p class="text-sm text-gray-500 m-0">Nada pendiente para hoy. Si el embudo tiene prospectos abiertos, agéndales la siguiente acción.</p>
        </div>
    @endif

    {{-- Actividades agendadas --}}
    @if ($actividades->count())
        <h2 class="text-base font-bold text-gray-900 mb-3">Actividades agendadas</h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
            @foreach ($actividades as $actividad)
                <div class="flex items-center gap-4 px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold uppercase tracking-wide">{{ \App\Models\CrmActivity::TIPOS[$actividad->tipo] ?? $actividad->tipo }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('crm.leads.show', $actividad->lead_id) }}" class="text-sm font-semibold text-gray-900 no-underline hover:text-indigo-600">{{ $actividad->lead->empresa ?: $actividad->lead->nombre }}</a>
                        <span class="block text-xs text-gray-500 truncate">{{ $actividad->descripcion }}</span>
                    </div>
                    <span class="shrink-0 text-xs text-gray-400 tabular-nums">{{ $actividad->programada_at?->format('d/m H:i') }}</span>
                    <form method="POST" action="{{ route('crm.actividades.completar', $actividad) }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 border-0 cursor-pointer">Hecho</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Sin próxima acción: la fuga silenciosa del embudo --}}
    @if ($sinSeguimiento->count())
        <h2 class="text-base font-bold text-gray-900 mb-1">Sin próxima acción <span class="text-gray-400 font-medium">({{ $sinSeguimiento->count() }})</span></h2>
        <p class="text-sm text-gray-500 mb-3">Estos prospectos están abiertos pero nadie va a hacer nada con ellos. Aquí es por donde se fuga el embudo.</p>
        <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
            @foreach ($sinSeguimiento as $lead)
                <a href="{{ route('crm.leads.show', $lead) }}"
                   class="flex items-center gap-4 px-4 py-3 no-underline hover:bg-amber-50/50 transition {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 truncate">{{ $lead->empresa ?: $lead->nombre }}</span>
                    </div>
                    <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">{{ $lead->stage->nombre }}</span>
                    @if ($lead->diasSinContacto() !== null)
                        <span class="shrink-0 text-xs text-gray-400 tabular-nums w-24 text-right">{{ $lead->diasSinContacto() }} días sin contacto</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
