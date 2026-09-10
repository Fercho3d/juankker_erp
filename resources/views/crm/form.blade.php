@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php $editando = $lead->exists; @endphp
<div class="max-w-3xl mx-auto px-6 py-8">

    <div class="mb-6">
        <a href="{{ $editando ? route('crm.leads.show', $lead) : route('crm.tablero') }}" class="text-sm text-gray-500 no-underline hover:text-gray-700">&larr; {{ __('Volver') }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $editando ? __('Editar prospecto') : __('Nuevo prospecto') }}</h1>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl">
            <ul class="m-0 pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $editando ? route('crm.leads.update', $lead) : route('crm.leads.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col gap-5">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Empresa') }}</label>
                <input type="text" name="empresa" value="{{ old('empresa', $lead->empresa) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Contacto') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="nombre" required value="{{ old('nombre', $lead->nombre) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Teléfono') }}</label>
                <input type="text" name="telefono" value="{{ old('telefono', $lead->telefono) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Correo') }}</label>
                <input type="email" name="email" value="{{ old('email', $lead->email) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Etapa') }}</label>
                <select name="stage_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                    @foreach ($etapas as $etapa)
                        <option value="{{ $etapa->id }}" {{ old('stage_id', $lead->stage_id) == $etapa->id ? 'selected' : '' }}>{{ __($etapa->nombre) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Origen') }}</label>
                <select name="origen" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                    @foreach ($origenes as $clave => $etiqueta)
                        <option value="{{ $clave }}" {{ old('origen', $lead->origen) === $clave ? 'selected' : '' }}>{{ __($etiqueta) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr class="border-gray-100 m-0">

        <div class="grid sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Valor del proyecto') }}</label>
                <input type="number" step="0.01" min="0" name="valor_estimado" value="{{ old('valor_estimado', $lead->valor_estimado ?: '') }}" placeholder="0.00"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm tabular-nums focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
                <span class="block text-xs text-gray-400 mt-1">{{ __('Implantación o pago único.') }}</span>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Mensualidad') }}</label>
                <input type="number" step="0.01" min="0" name="valor_mensual" value="{{ old('valor_mensual', $lead->valor_mensual ?: '') }}" placeholder="0.00"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm tabular-nums focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
                <span class="block text-xs text-gray-400 mt-1">{{ __('Lo que se repite cada mes.') }}</span>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Probabilidad %') }}</label>
                <input type="number" min="0" max="100" name="probabilidad" required value="{{ old('probabilidad', $lead->probabilidad ?? 20) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm tabular-nums focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
        </div>

        <hr class="border-gray-100 m-0">

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Próxima acción') }}</label>
                <input type="text" name="proxima_accion" value="{{ old('proxima_accion', $lead->proxima_accion) }}" placeholder="{{ __('Llamar para agendar diagnóstico') }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('¿Cuándo?') }}</label>
                <input type="datetime-local" name="proxima_accion_at"
                       value="{{ old('proxima_accion_at', $lead->proxima_accion_at?->format('Y-m-d\TH:i')) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Notas') }}</label>
            <textarea name="notas" rows="4" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">{{ old('notas', $lead->notas) }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 border-0 cursor-pointer">
                {{ $editando ? __('Guardar cambios') : __('Crear prospecto') }}
            </button>
            <a href="{{ $editando ? route('crm.leads.show', $lead) : route('crm.tablero') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">{{ __('Cancelar') }}</a>
        </div>
    </form>
</div>
@endsection
