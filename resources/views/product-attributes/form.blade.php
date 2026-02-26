@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-[900px] mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $attribute->exists ? 'Editar' : 'Nuevo' }} Atributo</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $attribute->exists ? 'Actualiza los datos del atributo' : 'Crea un nuevo atributo para variantes' }}</p>
        </div>
        <a href="{{ route('atributos-producto.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver
        </a>
    </div>

    <form method="POST" action="{{ $attribute->exists ? route('atributos-producto.update', $attribute) : route('atributos-producto.store') }}">
        @csrf
        @if($attribute->exists)
            @method('PUT')
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Información del Atributo</h2>
                    <p class="text-sm text-gray-500">Configura el tipo de atributo para variantes</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="nombre" class="block text-sm font-medium text-gray-900 mb-2">Nombre *</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="w-full px-3 py-3 border {{ $errors->has('nombre') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                        value="{{ old('nombre', $attribute->nombre) }}"
                        placeholder="Ej: Talla, Color, Peso"
                        required
                    >
                    @error('nombre')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="tipo" class="block text-sm font-medium text-gray-900 mb-2">Tipo *</label>
                    <select
                        id="tipo"
                        name="tipo"
                        class="w-full px-3 py-3 border {{ $errors->has('tipo') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                        required
                    >
                        <option value="select" {{ old('tipo', $attribute->tipo) === 'select' ? 'selected' : '' }}>Selección</option>
                        <option value="color" {{ old('tipo', $attribute->tipo) === 'color' ? 'selected' : '' }}>Color</option>
                        <option value="text" {{ old('tipo', $attribute->tipo) === 'text' ? 'selected' : '' }}>Texto</option>
                    </select>
                    @error('tipo')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Define cómo se mostrará este atributo en el formulario de productos</p>
                </div>
            </div>

            <div class="mb-4">
                <label for="orden" class="block text-sm font-medium text-gray-900 mb-2">Orden de visualización</label>
                <input
                    type="number"
                    id="orden"
                    name="orden"
                    class="w-full px-3 py-3 border {{ $errors->has('orden') ? 'border-red-400' : 'border-gray-200' }} rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                    value="{{ old('orden', $attribute->orden ?? 0) }}"
                    min="0"
                >
                @error('orden')
                    <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                @enderror
                <p class="text-xs text-gray-400 mt-1">Los atributos se mostrarán ordenados por este número</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Estado</h2>
                    <p class="text-sm text-gray-500">Configuración de activación</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer">
                    <input
                        type="checkbox"
                        class="w-[18px] h-[18px] accent-indigo-600"
                        name="activo"
                        value="1"
                        {{ old('activo', $attribute->activo ?? true) ? 'checked' : '' }}
                    >
                    Atributo activo
                </label>
                <p class="text-xs text-gray-400 mt-1">Los atributos inactivos no estarán disponibles para nuevos productos</p>
            </div>
        </div>

        <div class="flex items-center gap-4 justify-end mt-8">
            <a href="{{ route('atributos-producto.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Cancelar</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none">
                {{ $attribute->exists ? 'Actualizar' : 'Crear' }} Atributo
            </button>
        </div>
    </form>

    @if($attribute->exists)
        <div class="mt-8">
            <a href="{{ route('atributos-producto.valores.index', $attribute) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors no-underline">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
                Gestionar Valores del Atributo
            </a>
        </div>
    @endif
</div>
@endsection
