@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $marcados = old('permisos', $perfil->permisos ?? []);
    $alcance = old('crm_alcance', $perfil->crm_alcance ?? 'propios');
@endphp
<div class="max-w-2xl mx-auto px-6 py-8">
    <a href="{{ route('team.index') }}" class="text-sm text-gray-500 no-underline hover:text-gray-700">&larr; {{ __('Equipo') }}</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $perfil->exists ? __('Editar perfil') : __('Nuevo perfil') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5 mb-6">{{ __('Marca las partes del ERP a las que entra quien tenga este perfil. El menú se ajusta solo.') }}</p>

    @if ($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $perfil->exists ? route('team.role.update', $perfil) : route('team.role.store') }}" class="flex flex-col gap-5">
        @csrf
        @if ($perfil->exists) @method('PUT') @endif

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="nombre" class="block text-sm font-semibold text-gray-900 mb-1.5">{{ __('Nombre del perfil') }}</label>
            <input id="nombre" name="nombre" value="{{ old('nombre', $perfil->nombre) }}" required maxlength="60" placeholder="{{ __('Ej: Vendedor de mostrador') }}"
                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <span class="block text-sm font-semibold text-gray-900 mb-3">{{ __('Acceso a') }}</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach (\App\Models\Role::MODULOS as $clave => $etiqueta)
                    <label class="flex items-center gap-2.5 px-3 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="permisos[]" value="{{ $clave }}" @checked(in_array($clave, $marcados)) @if ($clave === 'crm') data-crm @endif class="w-4 h-4 accent-indigo-600">
                        <span class="text-sm text-gray-700">{{ __($etiqueta) }}</span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-3 m-0">{{ __('"Equipo y perfiles" y "Plan y facturación" conviene dejarlos sólo a quien administra.') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5" data-alcance>
            <span class="block text-sm font-semibold text-gray-900 mb-3">{{ __('En el embudo ve') }}</span>
            <div class="flex flex-col gap-2">
                <label class="flex items-start gap-2.5 px-3 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="crm_alcance" value="propios" @checked($alcance === 'propios') class="mt-0.5 w-4 h-4 accent-indigo-600">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">{{ __('Sólo sus prospectos') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Ve y trabaja nada más lo que tiene asignado. Lo nuevo que da de alta queda a su nombre.') }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 px-3 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="crm_alcance" value="todos" @checked($alcance === 'todos') class="mt-0.5 w-4 h-4 accent-indigo-600">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">{{ __('Todo el embudo') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Ve a todos los prospectos y puede asignarlos a otros vendedores. Para gerentes.') }}</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 cursor-pointer border-0">{{ $perfil->exists ? __('Guardar cambios') : __('Crear perfil') }}</button>
            <a href="{{ route('team.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400 no-underline">{{ __('Cancelar') }}</a>
        </div>
    </form>

    @if ($perfil->exists)
        <form method="POST" action="{{ route('team.role.destroy', $perfil) }}" class="mt-8 pt-6 border-t border-gray-200"
              onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('¿Eliminar el perfil :nombre?', ['nombre' => $perfil->nombre])) }})">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-rose-600 bg-white hover:border-rose-300 cursor-pointer">{{ __('Eliminar perfil') }}</button>
        </form>
    @endif
</div>

@push('scripts')
<script>
    // El alcance del embudo sólo importa si el perfil entra al CRM.
    (function () {
        var crm = document.querySelector('[data-crm]');
        var bloque = document.querySelector('[data-alcance]');
        var ajustar = function () { bloque.hidden = !crm.checked; };
        crm.addEventListener('change', ajustar);
        ajustar();
    })();
</script>
@endpush
@endsection
