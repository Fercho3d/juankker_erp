@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('crm.tablero') }}" class="text-sm text-gray-500 no-underline hover:text-gray-700">&larr; Embudo</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Papelera</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ number_format($leads->total()) }} descartados. No cuentan en el embudo ni vuelven a entrar cuando importas del DENUE.
        </p>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap items-center gap-2.5 mb-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar empresa, giro, teléfono…"
               class="w-full sm:w-64 px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
        <select name="motivo" onchange="this.form.submit()"
                class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
            <option value="">Cualquier motivo</option>
            @foreach (\App\Models\Lead::MOTIVOS_DESCARTE as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(request('motivo') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @if (request('search') || request('motivo'))
            <a href="{{ route('crm.papelera') }}" class="px-3 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-900 no-underline">Limpiar</a>
        @endif
    </form>

    @if ($leads->count())
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            @foreach ($leads as $lead)
                <div class="flex items-center gap-4 px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 truncate">{{ $lead->empresa ?: $lead->nombre }}</span>
                        <span class="block text-xs text-gray-500 truncate">
                            {{ collect([$lead->sector, $lead->rangoPersonal() ? $lead->rangoPersonal().' pers.' : null, $lead->municipio, $lead->telefono])->filter()->implode(' · ') }}
                        </span>
                    </div>
                    <span class="shrink-0 hidden sm:inline text-[11px] px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-medium">
                        {{ \App\Models\Lead::MOTIVOS_DESCARTE[$lead->motivo_descarte] ?? 'Sin motivo' }}
                    </span>
                    <span class="shrink-0 hidden md:inline text-xs text-gray-400 w-24 text-right">{{ $lead->deleted_at->diffForHumans() }}</span>
                    <form method="POST" action="{{ route('crm.leads.restaurar', $lead->id) }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold text-gray-700 hover:border-indigo-400 hover:text-indigo-700 bg-white cursor-pointer">Restaurar</button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $leads->links() }}</div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center">
            <p class="text-sm text-gray-500 m-0">
                {{ request('search') || request('motivo') ? 'Nada descartado con esos filtros.' : 'La papelera está vacía. Arrastra al final del embudo a quien no sea prospecto.' }}
            </p>
        </div>
    @endif
</div>
@endsection
