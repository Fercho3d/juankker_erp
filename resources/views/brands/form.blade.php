@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-[900px] mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $brand->exists ? 'Editar' : 'Nueva' }} Marca</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $brand->exists ? 'Actualiza los datos de la marca' : 'Crea una nueva marca de productos' }}</p>
        </div>
        <a href="{{ route('marcas.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver
        </a>
    </div>

    <form method="POST" action="{{ $brand->exists ? route('marcas.update', $brand) : route('marcas.store') }}">
        @csrf
        @if($brand->exists)
            @method('PUT')
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Información de la Marca</h2>
                    <p class="text-sm text-gray-500">Datos básicos de la marca</p>
                </div>
            </div>

            <div class="mb-4">
                <label for="nombre" class="block text-sm font-medium text-gray-900 mb-2">Nombre *</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    class="w-full px-3 py-3 border rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 {{ $errors->has('nombre') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20' }}"
                    value="{{ old('nombre', $brand->nombre) }}"
                    required
                >
                @error('nombre')
                    <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="mb-4">
                <label for="descripcion" class="block text-sm font-medium text-gray-900 mb-2">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="w-full px-3 py-3 border rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 resize-y min-h-[80px] {{ $errors->has('descripcion') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20' }}"
                    rows="3"
                >{{ old('descripcion', $brand->descripcion) }}</textarea>
                @error('descripcion')
                    <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                @enderror
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
                        name="activo"
                        value="1"
                        class="w-[18px] h-[18px] accent-indigo-600 cursor-pointer"
                        {{ old('activo', $brand->activo ?? true) ? 'checked' : '' }}
                    >
                    Marca activa
                </label>
                <p class="text-xs text-gray-400 mt-1">Las marcas inactivas no estarán disponibles para nuevos productos</p>
            </div>
        </div>

        <div class="flex items-center gap-4 justify-end mt-8">
            <a href="{{ route('marcas.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Cancelar</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none">
                {{ $brand->exists ? 'Actualizar' : 'Crear' }} Marca
            </button>
        </div>
    </form>
</div>
@endsection
