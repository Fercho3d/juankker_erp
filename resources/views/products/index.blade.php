@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Productos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Administra tu catálogo de productos</p>
        </div>
        <a href="{{ route('productos.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Nuevo Producto
        </a>
    </div>

    @if(session('status') === 'product-created')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Producto creado exitosamente
        </div>
    @endif

    @if(session('status') === 'product-updated')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Producto actualizado exitosamente
        </div>
    @endif

    @if(session('status') === 'product-deleted')
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            Producto eliminado exitosamente
        </div>
    @endif

    <div class="mb-6">
        <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    class="w-full pl-11 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 transition"
                    placeholder="Buscar productos..."
                    value="{{ request('search') }}"
                >
            </div>

            <select name="category_id" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="brand_id" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                <option value="">Todas las marcas</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                        {{ $brand->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="tipo_producto" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                <option value="">Todos los tipos</option>
                <option value="simple" {{ request('tipo_producto') === 'simple' ? 'selected' : '' }}>Simple</option>
                <option value="variable" {{ request('tipo_producto') === 'variable' ? 'selected' : '' }}>Variable</option>
            </select>

            <select name="status" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activos</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivos</option>
            </select>

            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer">Buscar</button>

            @if(request('search') || request('category_id') || request('brand_id') || request('tipo_producto') || request('status') !== null)
                <a href="{{ route('productos.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($products->count() > 0)
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Código</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Nombre</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Categoría</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Marca</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Tipo</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Precio Venta</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Stock</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Estado</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                            <td class="px-4 py-3.5 text-gray-900 align-middle"><strong>{{ $product->codigo }}</strong></td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $product->nombre }}</td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($product->category)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-blue-50 text-blue-800">{{ $product->category->nombre }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Sin categoría</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($product->brand)
                                    {{ $product->brand->nombre }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($product->tipo_producto === 'simple')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-emerald-50 text-emerald-800">Simple</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-violet-50 text-violet-800">Variable</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">${{ number_format($product->precio_venta, 2) }}</td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @php
                                    $stock = $product->getStockTotal();
                                    $isLow = $product->hasLowStock();
                                @endphp
                                @if($isLow)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-amber-50 text-amber-800">{{ $stock }}</span>
                                @else
                                    {{ $stock }}
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                @if($product->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-emerald-50 text-emerald-800">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-500">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                <div class="flex gap-2">
                                    <a href="{{ route('productos.edit', $product) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="Editar">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('productos.destroy', $product) }}"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
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
                    Mostrando {{ $products->firstItem() }} - {{ $products->lastItem() }} de {{ $products->total() }} productos
                </div>
                <div class="pagination-links">
                    {{ $products->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-16 px-8 text-gray-500">
                <svg class="mx-auto mb-4 opacity-40" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay productos</h3>
                <p class="text-sm">Comienza creando tu primer producto.</p>
            </div>
        @endif
    </div>
</div>
@endsection
