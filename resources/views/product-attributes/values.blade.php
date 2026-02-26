@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Valores: {{ $attribute->nombre }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestiona los valores disponibles para este atributo</p>
        </div>
        <a href="{{ route('atributos-producto.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver a Atributos
        </a>
    </div>

    @if(session('status') === 'value-created')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Valor creado exitosamente
        </div>
    @endif

    @if(session('status') === 'value-updated')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Valor actualizado exitosamente
        </div>
    @endif

    @if(session('status') === 'value-deleted')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Valor eliminado exitosamente
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
        <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
            <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Agregar Nuevo Valor</h2>
                <p class="text-sm text-gray-500">Crea un valor para {{ $attribute->nombre }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('atributos-producto.valores.store', $attribute) }}">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="mb-4">
                    <label for="valor" class="block text-sm font-medium text-gray-900 mb-2">Valor *</label>
                    <input
                        type="text"
                        id="valor"
                        name="valor"
                        class="w-full px-3 py-3 border {{ $errors->has('valor') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                        value="{{ old('valor') }}"
                        placeholder="Ej: XL, Rojo, 2kg"
                        required
                    >
                    @error('valor')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                @if($attribute->tipo === 'color')
                    <div class="mb-4">
                        <label for="codigo_color" class="block text-sm font-medium text-gray-900 mb-2">Código Color (Hex)</label>
                        <input
                            type="text"
                            id="codigo_color"
                            name="codigo_color"
                            class="w-full px-3 py-3 border {{ $errors->has('codigo_color') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                            value="{{ old('codigo_color') }}"
                            placeholder="#FF0000"
                            pattern="#[0-9A-Fa-f]{6}"
                            maxlength="7"
                        >
                        @error('codigo_color')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                @endif

                <div class="mb-4">
                    <label for="orden" class="block text-sm font-medium text-gray-900 mb-2">Orden</label>
                    <input
                        type="number"
                        id="orden"
                        name="orden"
                        class="w-full px-3 py-3 border {{ $errors->has('orden') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                        value="{{ old('orden', $values->count()) }}"
                        min="0"
                    >
                    @error('orden')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none">Agregar Valor</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
        @if($values->count() > 0)
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Valor</th>
                        @if($attribute->tipo === 'color')
                            <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Color</th>
                        @endif
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Orden</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Estado</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($values as $value)
                        <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <strong>{{ $value->valor }}</strong>
                            </td>
                            @if($attribute->tipo === 'color')
                                <td class="px-4 py-3.5 text-gray-900 align-middle">
                                    @if($value->codigo_color)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded border border-gray-300" style="background-color: {{ $value->codigo_color }}"></div>
                                            <span>{{ $value->codigo_color }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Sin color</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $value->orden }}</td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($value->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-emerald-50 text-emerald-800">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('atributos-producto.valores.destroy', [$attribute, $value]) }}"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este valor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 transition-colors cursor-pointer border-none bg-transparent" title="Eliminar">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-16 px-8 text-gray-500">
                <svg class="mx-auto mb-4 opacity-40" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay valores</h3>
                <p class="text-sm">Agrega valores para este atributo (ej: XS, S, M, L, XL).</p>
            </div>
        @endif
    </div>
</div>
@endsection
