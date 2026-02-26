@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Inventario</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gestiona el stock de tus productos</p>
            </div>

            <form method="GET" action="{{ route('inventario.index') }}" class="flex items-center gap-3">
                <div class="relative min-w-[300px]">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" width="20"
                        height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input type="text" name="search"
                        class="w-full pl-11 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 transition"
                        placeholder="Buscar por nombre, SKU o código..." value="{{ request('search') }}">
                </div>
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('inventario.index') }}"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Limpiar</a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div
                class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                Imagen</th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                Producto</th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                SKU / Barras</th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                Precio Venta</th>
                            <th
                                class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                Stock Actual</th>
                            <th
                                class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($variants as $variant)
                            <tr
                                class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                                <td class="px-4 py-3.5 align-middle w-20">
                                    @if($variant->imagen || $variant->product->imagen)
                                        <img src="{{ asset('storage/' . ($variant->imagen ?? $variant->product->imagen)) }}"
                                            alt="{{ $variant->product->nombre }}"
                                            class="w-12 h-12 object-cover rounded-lg border border-gray-100">
                                    @else
                                        <div
                                            class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-900 align-middle">
                                    <div class="font-medium text-gray-900">{{ $variant->product->nombre }}</div>
                                    @if($variant->attributeValues->count() > 0)
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ $variant->attributeValues->pluck('valor')->join(' / ') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 align-middle">
                                    @if($variant->sku)
                                        <div class="flex items-center gap-1.5"><span
                                                class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">SKU</span>
                                    {{ $variant->sku }}</div> @endif
                                    @if($variant->codigo_barras)
                                        <div class="flex items-center gap-1.5 mt-1"><span
                                                class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">EAN</span>
                                    {{ $variant->codigo_barras }}</div> @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-900 align-middle font-medium">
                                    ${{ number_format($variant->precio_venta, 2) }}
                                </td>
                                <td class="px-4 py-3.5 align-middle text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $variant->stock_actual > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $variant->stock_actual }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 align-middle text-center">
                                    <button type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                                        data-bs-toggle="modal" data-bs-target="#adjustStockModal{{ $variant->id }}">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path d="M12 4v16m8-8H4" />
                                        </svg>
                                        Ajustar
                                    </button>

                                    <!-- Styles for Modal (using Bootstrap classes as base but enhancing content) -->
                                    <div class="modal fade" id="adjustStockModal{{ $variant->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-2xl shadow-xl overflow-hidden border-0">
                                                <form action="{{ route('inventario.update', $variant->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div
                                                        class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                                        <h5 class="text-lg font-semibold text-gray-900">Ajustar Stock</h5>
                                                        <button type="button" class="btn-close opacity-50 hover:opacity-100"
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="p-6">
                                                        <div class="text-start mb-6">
                                                            <div class="text-sm text-gray-500 mb-1">Stock Actual</div>
                                                            <div
                                                                class="text-4xl font-bold {{ $variant->stock_actual > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                                {{ $variant->stock_actual }}
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900 mt-2">
                                                                {{ $variant->getVariantName() }}</div>
                                                        </div>

                                                        <div class="space-y-4 text-start">
                                                            <div>
                                                                <label
                                                                    class="block text-sm font-medium text-gray-700 mb-1.5">Operación</label>
                                                                <select class="form-control" name="operation" required>
                                                                    <option value="add">Agregar (+)</option>
                                                                    <option value="subtract">Restar (-)</option>
                                                                    <option value="set">Definir Nuevo Total (=)</option>
                                                                </select>
                                                            </div>

                                                            <div>
                                                                <label
                                                                    class="block text-sm font-medium text-gray-700 mb-1.5">Cantidad</label>
                                                                <input type="number" class="form-control" name="quantity"
                                                                    min="1" required>
                                                            </div>

                                                            <div>
                                                                <label
                                                                    class="block text-sm font-medium text-gray-700 mb-1.5">Notas
                                                                    (Opcional)</label>
                                                                <input type="text" class="form-control" name="notes"
                                                                    placeholder="Razón del ajuste...">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                                                        <button type="button"
                                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                                            data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit"
                                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Guardar
                                                            Ajuste</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-16 px-8 text-gray-500">
                                    <svg class="mx-auto mb-4 opacity-40" width="48" height="48" fill="none"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <rect x="2" y="7" width="20" height="14" rx="2" />
                                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">No hay productos</h3>
                                    <p class="text-sm">Intenta ajustar los filtros de búsqueda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($variants->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $variants->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Custom overrides for Bootstrap modal to blend with Tailwind */
        .modal-content {
            border-radius: 1rem;
            border: none;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #1f2937;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #e5e7eb;
            appearance: none;
            border-radius: 0.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            color: #1f2937;
            background-color: #fff;
            border-color: #6366f1;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
    </style>
@endsection