@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('crm.tablero') }}" class="text-sm text-gray-500 no-underline hover:text-gray-700">&larr; {{ __('Embudo') }}</a>
        <div class="flex flex-wrap items-start justify-between gap-4 mt-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $lead->empresa ?: $lead->nombre }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $lead->empresa ? $lead->nombre.' · ' : '' }}{{ __(\App\Models\Lead::ORIGENES[$lead->origen] ?? ($lead->origen ?? '')) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.leads.edit', $lead) }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">{{ __('Editar') }}</a>
                <form method="POST" action="{{ route('crm.leads.descartar', $lead) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="motivo" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500" aria-label="{{ __('Motivo del descarte') }}">
                        @foreach (\App\Models\Lead::MOTIVOS_DESCARTE as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-rose-600 hover:border-rose-300 bg-transparent cursor-pointer">{{ __('Descartar') }}</button>
                </form>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ __('Guardado.') }}</div>
    @endif
    @if ($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl">
            <ul class="m-0 pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">

        {{-- Columna principal: registrar y ver seguimiento --}}
        <div class="flex flex-col gap-6">

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-bold text-gray-900 m-0 mb-4">{{ __('Registrar seguimiento') }}</h2>
                <form method="POST" action="{{ route('crm.actividades.store', $lead) }}" class="flex flex-col gap-4">
                    @csrf
                    <div class="flex flex-wrap gap-2">
                        @foreach (['llamada' => 'Llamada', 'whatsapp' => 'WhatsApp', 'email' => 'Correo', 'visita' => 'Visita', 'nota' => 'Nota'] as $valor => $etiqueta)
                            <label class="cursor-pointer">
                                <input type="radio" name="tipo" value="{{ $valor }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                <span class="inline-block px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">{{ __($etiqueta) }}</span>
                            </label>
                        @endforeach
                    </div>

                    <textarea name="descripcion" rows="3" required placeholder="{{ __('¿Qué pasó? Lo que te dijo, lo que quedó pendiente…') }}"
                              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"></textarea>

                    <div class="grid sm:grid-cols-2 gap-3 pt-1 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 mt-3">{{ __('Próxima acción') }}</label>
                            <input type="text" name="proxima_accion" placeholder="{{ __('Enviar propuesta') }}"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 mt-3">{{ __('¿Cuándo?') }}</label>
                            <input type="datetime-local" name="proxima_accion_at"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 border-0 cursor-pointer">{{ __('Registrar') }}</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-bold text-gray-900 m-0 mb-4">{{ __('Historial') }} <span class="text-gray-400 font-medium">({{ $actividades->count() }})</span></h2>
                @if ($actividades->count())
                    <div class="flex flex-col">
                        @foreach ($actividades as $actividad)
                            <div class="flex gap-3.5 {{ !$loop->last ? 'pb-4 mb-4 border-b border-gray-100' : '' }}">
                                <span class="shrink-0 mt-0.5 text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wide h-fit
                                    {{ $actividad->tipo === 'etapa' ? 'bg-gray-100 text-gray-600' : 'bg-indigo-50 text-indigo-700' }}">
                                    {{ \App\Models\CrmActivity::TIPOS[$actividad->tipo] ?? $actividad->tipo }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-900 m-0 whitespace-pre-line">{{ $actividad->descripcion }}</p>
                                    <span class="block text-xs text-gray-400 mt-1">
                                        {{ $actividad->created_at->format('d/m/Y H:i') }}{{ $actividad->user ? ' · '.$actividad->user->name : '' }}
                                        @if (!$actividad->completada_at && $actividad->programada_at) · <span class="text-amber-600 font-semibold">{{ __('pendiente') }}</span> @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 m-0">{{ __('Todavía no hay movimientos.') }}</p>
                @endif
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="flex flex-col gap-4">

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Etapa') }}</span>
                <form method="POST" action="{{ route('crm.leads.mover', $lead) }}" class="mt-2">
                    @csrf
                    <select name="stage_id" onchange="this.form.submit()"
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                        @foreach ($etapas as $etapa)
                            <option value="{{ $etapa->id }}" {{ $lead->stage_id == $etapa->id ? 'selected' : '' }}>{{ __($etapa->nombre) }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="grid grid-cols-2 gap-4 mt-5 pt-4 border-t border-gray-100">
                    <div>
                        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Proyecto') }}</span>
                        <span class="block text-lg font-bold text-gray-900 tabular-nums">${{ number_format($lead->valor_estimado, 0) }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Mensual') }}</span>
                        <span class="block text-lg font-bold text-emerald-600 tabular-nums">${{ number_format($lead->valor_mensual, 0) }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Probabilidad') }}</span>
                        <span class="block text-lg font-bold text-gray-900 tabular-nums">{{ $lead->probabilidad }}%</span>
                    </div>
                    <div>
                        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Ponderado') }}</span>
                        <span class="block text-lg font-bold text-indigo-600 tabular-nums">${{ number_format($lead->valorPonderado(), 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3">
                <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Contacto') }}</span>
                @if ($lead->telefono)
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-sm text-gray-900 tabular-nums">{{ $lead->telefono }}</span>
                        @if ($tel = $lead->telefonoDigitos())
                            <a href="tel:+52{{ $tel }}" class="text-xs font-semibold text-indigo-600 no-underline hover:underline">{{ __('Llamar') }}</a>
                            <a href="https://wa.me/52{{ $tel }}" target="_blank" rel="noopener" class="text-xs font-semibold text-emerald-600 no-underline hover:underline">{{ __('WhatsApp') }}</a>
                        @endif
                    </div>
                @endif
                @if ($lead->email)
                    <a href="mailto:{{ $lead->email }}" class="text-sm text-gray-900 no-underline hover:text-indigo-600 break-all">{{ $lead->email }}</a>
                @endif
                @if (!$lead->telefono && !$lead->email)
                    <span class="text-sm text-gray-400">{{ __('Sin datos de contacto.') }}</span>
                @endif

                <div class="pt-3 border-t border-gray-100">
                    <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold mb-1">{{ __('Responsable') }}</span>
                    @if ($responsables->count())
                        <form method="POST" action="{{ route('crm.leads.asignar', $lead) }}">
                            @csrf
                            <select name="owner_id" onchange="this.form.submit()" aria-label="{{ __('Responsable') }}"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                                @unless ($responsables->contains('id', $lead->owner_id))
                                    <option value="" selected disabled>{{ $lead->owner?->name ?? __('Sin asignar') }}</option>
                                @endunless
                                @foreach ($responsables as $responsable)
                                    <option value="{{ $responsable->id }}" @selected($lead->owner_id === $responsable->id)>{{ $responsable->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <span class="block text-sm text-gray-900">{{ $lead->owner?->name ?? __('Sin asignar') }}</span>
                    @endif
                </div>

                @if ($lead->proxima_accion_at)
                    <div class="pt-3 border-t border-gray-100">
                        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('Próxima acción') }}</span>
                        <span class="block text-sm font-semibold {{ $lead->estaVencido() ? 'text-rose-600' : 'text-gray-900' }} mt-1">
                            {{ $lead->proxima_accion ?: __('Dar seguimiento') }}
                        </span>
                        <span class="block text-xs text-gray-500">{{ $lead->proxima_accion_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>

            {{-- Convertir en cliente del ERP --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold mb-2">{{ __('Cliente del ERP') }}</span>
                @if ($lead->client_id)
                    <a href="{{ route('clientes.edit', $lead->client_id) }}" class="text-sm font-semibold text-indigo-600 no-underline">{{ $lead->client->razon_social ?? __('Ver cliente') }} &rarr;</a>
                @else
                    <p class="text-xs text-gray-500 m-0 mb-3">{{ __('Al ganarlo, dalo de alta como cliente para poder facturarle.') }}</p>
                    <form method="POST" action="{{ route('crm.leads.convertir', $lead) }}" class="flex flex-col gap-2">
                        @csrf
                        <input type="text" name="razon_social" required value="{{ old('razon_social', $lead->empresa ?: $lead->nombre) }}" placeholder="{{ __('Razón social') }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        <input type="text" name="rfc" required placeholder="{{ __('RFC') }}" maxlength="13"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm uppercase focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 border-0 cursor-pointer">{{ __('Convertir en cliente') }}</button>
                    </form>
                @endif
            </div>

            @if ($lead->notas)
                <div class="bg-amber-50 rounded-xl border border-amber-200 p-5">
                    <span class="block text-[11px] uppercase tracking-wider text-amber-700 font-semibold mb-2">{{ __('Notas') }}</span>
                    <p class="text-sm text-gray-800 m-0 whitespace-pre-line">{{ $lead->notas }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
