@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Atributos de Productos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Define los atributos para variantes (Talla, Color, etc.)</p>
        </div>
        <a href="{{ route('atributos-producto.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Nuevo Atributo
        </a>
    </div>

    @if(session('status') === 'attribute-created')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Atributo creado exitosamente
        </div>
    @endif

    @if(session('status') === 'attribute-updated')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Atributo actualizado exitosamente
        </div>
    @endif

    @if(session('status') === 'attribute-deleted')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Atributo eliminado exitosamente
        </div>
    @endif

    <div class="mb-6">
        <form method="GET" action="{{ route('atributos-producto.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    class="w-full pl-11 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 transition"
                    placeholder="Buscar atributos..."
                    value="{{ request('search') }}"
                >
            </div>

            <select name="status" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activos</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivos</option>
            </select>

            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer">Buscar</button>

            @if(request('search') || request('status') !== null)
                <a href="{{ route('atributos-producto.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($attributes->count() > 0)
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Nombre</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Tipo</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Valores</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Orden</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Estado</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attributes as $attribute)
                        <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <strong>{{ $attribute->nombre }}</strong>
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($attribute->tipo === 'select')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-blue-50 text-blue-800">Selección</span>
                                @elseif($attribute->tipo === 'color')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-violet-50 text-violet-800">Color</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Texto</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <a href="{{ route('atributos-producto.valores.index', $attribute) }}" class="text-indigo-600 hover:text-indigo-800 no-underline">
                                    {{ $attribute->values->count() }} valores
                                </a>
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $attribute->orden }}</td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($attribute->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-emerald-50 text-emerald-800">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('atributos-producto.valores.index', $attribute) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors no-underline" title="Gestionar valores">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M3 12h18M3 6h18M3 18h18"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('atributos-producto.edit', $attribute) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors no-underline" title="Editar">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('atributos-producto.destroy', $attribute) }}"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este atributo?');">
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

            <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-3.5 border-t border-gray-200 text-sm">
                <div class="text-gray-500">
                    Mostrando {{ $attributes->firstItem() }} - {{ $attributes->lastItem() }} de {{ $attributes->total() }} atributos
                </div>
                <div>
                    {{ $attributes->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-16 px-8 text-gray-500">
                <svg class="mx-auto mb-4 opacity-40" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay atributos</h3>
                <p class="text-sm">Crea atributos como Talla, Color o Peso para tus productos variables.</p>
            </div>
        @endif
    </div>
</div>
@endsection
