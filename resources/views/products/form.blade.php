@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $product->exists ? 'Editar' : 'Nuevo' }} Producto</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $product->exists ? 'Actualiza los datos del producto' : 'Crea un nuevo producto' }}</p>
        </div>
        <a href="{{ route('productos.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver
        </a>
    </div>

    @if($errors->any())
        <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
            </svg>
            <div>
                <strong>Hay errores en el formulario:</strong>
                <ul class="mt-2 ml-6 list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $product->exists ? route('productos.update', $product) : route('productos.store') }}" enctype="multipart/form-data">
        @csrf
        @if($product->exists)
            @method('PUT')
        @endif

        {{-- Sección 1: Información Básica --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Información Básica</h2>
                    <p class="text-sm text-gray-500">Datos generales del producto</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-900 mb-2">Tipo de Producto *</label>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer">
                        <input
                            type="radio"
                            name="tipo_producto"
                            value="simple"
                            {{ old('tipo_producto', $product->tipo_producto ?? 'simple') === 'simple' ? 'checked' : '' }}
                            {{ $product->exists ? 'disabled' : '' }}
                        >
                        <span>Producto Simple</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer">
                        <input
                            type="radio"
                            name="tipo_producto"
                            value="variable"
                            {{ old('tipo_producto', $product->tipo_producto) === 'variable' ? 'checked' : '' }}
                            {{ $product->exists ? 'disabled' : '' }}
                        >
                        <span>Producto Variable</span>
                    </label>
                </div>
                @if($product->exists)
                    <input type="hidden" name="tipo_producto" value="{{ $product->tipo_producto }}">
                    <p class="text-xs text-gray-400 mt-1">El tipo de producto no se puede cambiar una vez creado</p>
                @else
                    <p class="text-xs text-gray-400 mt-1">Simple: un solo producto. Variable: producto con variantes (tallas, colores, etc.)</p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="codigo" class="block text-sm font-medium text-gray-900 mb-2">Código Interno *</label>
                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('codigo') border-red-400 @enderror"
                        value="{{ old('codigo', $product->codigo) }}"
                        placeholder="PROD-001"
                        required
                    >
                    @error('codigo')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="nombre" class="block text-sm font-medium text-gray-900 mb-2">Nombre *</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('nombre') border-red-400 @enderror"
                        value="{{ old('nombre', $product->nombre) }}"
                        required
                    >
                    @error('nombre')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="descripcion" class="block text-sm font-medium text-gray-900 mb-2">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 resize-y min-h-[80px] @error('descripcion') border-red-400 @enderror"
                    rows="3"
                >{{ old('descripcion', $product->descripcion) }}</textarea>
                @error('descripcion')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-900 mb-2">Categoría</label>
                    <select
                        id="category_id"
                        name="category_id"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('category_id') border-red-400 @enderror"
                    >
                        <option value="">Seleccionar categoría</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="brand_id" class="block text-sm font-medium text-gray-900 mb-2">Marca</label>
                    <select
                        id="brand_id"
                        name="brand_id"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('brand_id') border-red-400 @enderror"
                    >
                        <option value="">Seleccionar marca</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                {{ $brand->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('brand_id')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-900 mb-2">Proveedor</label>
                    <select
                        id="supplier_id"
                        name="supplier_id"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('supplier_id') border-red-400 @enderror"
                    >
                        <option value="">Seleccionar proveedor</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->razon_social }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sección 2: Precios --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Precios</h2>
                    <p class="text-sm text-gray-500">Costos y precios de venta</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="mb-4">
                    <label for="precio_compra" class="block text-sm font-medium text-gray-900 mb-2">Precio de Compra</label>
                    <input
                        type="number"
                        id="precio_compra"
                        name="precio_compra"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('precio_compra') border-red-400 @enderror"
                        value="{{ old('precio_compra', $product->precio_compra) }}"
                        step="0.01"
                        min="0"
                    >
                    @error('precio_compra')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="precio_venta" class="block text-sm font-medium text-gray-900 mb-2">Precio de Venta *</label>
                    <input
                        type="number"
                        id="precio_venta"
                        name="precio_venta"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('precio_venta') border-red-400 @enderror"
                        value="{{ old('precio_venta', $product->precio_venta) }}"
                        step="0.01"
                        min="0"
                        required
                    >
                    @error('precio_venta')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="precio_mayoreo" class="block text-sm font-medium text-gray-900 mb-2">Precio Mayoreo</label>
                    <input
                        type="number"
                        id="precio_mayoreo"
                        name="precio_mayoreo"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('precio_mayoreo') border-red-400 @enderror"
                        value="{{ old('precio_mayoreo', $product->precio_mayoreo) }}"
                        step="0.01"
                        min="0"
                    >
                    @error('precio_mayoreo')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sección 3: Inventario --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Inventario</h2>
                    <p class="text-sm text-gray-500">Control de stock y unidades</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="unidad_medida" class="block text-sm font-medium text-gray-900 mb-2">Unidad de Medida *</label>
                    <select
                        id="unidad_medida"
                        name="unidad_medida"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('unidad_medida') border-red-400 @enderror"
                        required
                    >
                        @foreach($unidadesMedida as $key => $label)
                            <option value="{{ $key }}" {{ old('unidad_medida', $product->unidad_medida ?? 'pieza') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('unidad_medida')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="stock_minimo" class="block text-sm font-medium text-gray-900 mb-2">Stock Mínimo</label>
                    <input
                        type="number"
                        id="stock_minimo"
                        name="stock_minimo"
                        class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('stock_minimo') border-red-400 @enderror"
                        value="{{ old('stock_minimo', $product->stock_minimo ?? 0) }}"
                        min="0"
                    >
                    @error('stock_minimo')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Alerta cuando el stock esté por debajo de este número</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer">
                    <input
                        type="checkbox"
                        name="permite_decimales"
                        value="1"
                        class="w-[18px] h-[18px] accent-indigo-600 cursor-pointer"
                        {{ old('permite_decimales', $product->permite_decimales) ? 'checked' : '' }}
                    >
                    Permite cantidades decimales
                </label>
                <p class="text-xs text-gray-400 mt-1">Útil para productos vendidos por peso o volumen (ej: 1.5 kg)</p>
            </div>

            @if(!$product->exists || $product->tipo_producto === 'simple')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="simple-product-fields">
                    <div class="mb-4">
                        <label for="stock_inicial" class="block text-sm font-medium text-gray-900 mb-2">Stock Inicial</label>
                        <input
                            type="number"
                            id="stock_inicial"
                            name="stock_inicial"
                            class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                            value="{{ old('stock_inicial', $product->exists ? $product->variants->first()?->stock_actual : 0) }}"
                            min="0"
                        >
                        <p class="text-xs text-gray-400 mt-1">Cantidad inicial en inventario</p>
                    </div>

                    <div class="mb-4">
                        <label for="codigo_barras" class="block text-sm font-medium text-gray-900 mb-2">Código de Barras</label>
                        <input
                            type="text"
                            id="codigo_barras"
                            name="codigo_barras"
                            class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20"
                            value="{{ old('codigo_barras', $product->exists ? $product->variants->first()?->codigo_barras : '') }}"
                            placeholder="7501234567890"
                        >
                        <p class="text-xs text-gray-400 mt-1">EAN, UPC u otro código de barras</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sección 3.5: Variantes (solo para productos variables) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow hidden" id="variants-section">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Variantes del Producto</h2>
                    <p class="text-sm text-gray-500">Genera combinaciones de atributos (tallas, colores, etc.)</p>
                </div>
            </div>

            @if(!$product->exists || $product->tipo_producto === 'variable')
                @if(!$product->exists)
                    {{-- Creating new variable product --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Selecciona los atributos que aplican:</label>
                        @foreach($attributes as $attribute)
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer mb-2">
                                    <input type="checkbox" class="attribute-checkbox w-[18px] h-[18px] accent-indigo-600 cursor-pointer" data-attribute-id="{{ $attribute->id }}" data-attribute-name="{{ $attribute->nombre }}">
                                    <strong>{{ $attribute->nombre }}</strong>
                                </label>
                                
                                <div class="attribute-values hidden ml-8 mt-2" id="values-{{ $attribute->id }}">
                                    @if($attribute->values->count() > 0)
                                        <div class="flex flex-wrap gap-4">
                                            @foreach($attribute->values as $value)
                                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                                                    <input type="checkbox" class="attribute-value-checkbox w-4 h-4 accent-indigo-600 cursor-pointer" data-value-id="{{ $value->id }}" data-value-name="{{ $value->valor }}" data-attribute-id="{{ $attribute->id }}">
                                                    {{ $value->valor }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg inline-flex items-center gap-2">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Este atributo no tiene valores registrados. <a href="{{ route('atributos-producto.index') }}" class="underline hover:text-amber-800" target="_blank">Agregar valores</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none mb-6 shadow-sm shadow-indigo-200" onclick="generateVariants()">
                        Generar Variantes
                    </button>

                    <div id="variants-table-container" class="hidden">
                        <h3 class="mb-4 text-lg font-semibold">Variantes y Precios</h3>
                        <div id="variants-table" class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm"></div>
                        <p class="text-xs text-gray-500 mt-2 text-right">* Los precios y stock se pueden ajustar individualmente después.</p>
                    </div>
                @else
                    {{-- Editing existing variable product --}}
                    <div id="existing-variants">
                        <h3 class="mb-4 text-lg font-semibold">Variantes Existentes</h3>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm border-collapse">
                                <thead class="bg-gray-50 border-b-2 border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">SKU</th>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Variante</th>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Precio Venta</th>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Stock</th>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Código Barras</th>
                                        <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Activo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->variants as $index => $variant)
                                        <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                                                <input type="text" name="variants[{{ $index }}][sku]" class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono" value="{{ $variant->sku }}" required>
                                            </td>
                                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                                @php
                                                    $attrs = $variant->attributeValues->pluck('valor')->join(' / ');
                                                    $attrIds = $variant->attributeValues->pluck('id')->join(',');
                                                @endphp
                                                <input type="hidden" name="variants[{{ $index }}][attribute_value_ids]" value="{{ $attrIds }}">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $attrs ?: 'Sin atributos' }}</span>
                                            </td>
                                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                                <input type="number" name="variants[{{ $index }}][precio_venta]" class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" value="{{ $variant->precio_venta }}" step="0.01" required>
                                            </td>
                                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                                <input type="number" name="variants[{{ $index }}][stock_actual]" class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" value="{{ $variant->stock_actual }}" min="0">
                                            </td>
                                            <td class="px-4 py-3.5 text-gray-900 align-middle">
                                                <input type="text" name="variants[{{ $index }}][codigo_barras]" class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" value="{{ $variant->codigo_barras }}">
                                            </td>
                                            <td class="px-4 py-3.5 text-gray-900 align-middle text-center">
                                                <input type="checkbox" name="variants[{{ $index }}][activo]" class="w-4 h-4 accent-indigo-600 cursor-pointer rounded border-gray-300" value="1" {{ $variant->activo ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Sección 4: Datos Fiscales --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Datos Fiscales</h2>
                    <p class="text-sm text-gray-500">Información para facturación</p>
                </div>
            </div>

            <div class="mb-4">
                <label for="codigo_sat" class="block text-sm font-medium text-gray-900 mb-2">Código SAT</label>
                <select
                    id="codigo_sat"
                    name="codigo_sat"
                    class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('codigo_sat') border-red-400 @enderror"
                >
                    <option value="">Seleccionar código SAT</option>
                    @foreach($codigosSat as $codigo => $descripcion)
                        <option value="{{ $codigo }}" {{ old('codigo_sat', $product->codigo_sat) === $codigo ? 'selected' : '' }}>
                            {{ $descripcion }}
                        </option>
                    @endforeach
                </select>
                @error('codigo_sat')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                @enderror
                <p class="text-xs text-gray-400 mt-1">Requerido para generar facturas electrónicas (CFDI)</p>
            </div>
        </div>

        {{-- Sección 5: Estado --}}
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
                        {{ old('activo', $product->activo ?? true) ? 'checked' : '' }}
                    >
                    Producto activo
                </label>
                <p class="text-xs text-gray-400 mt-1">Los productos inactivos no estarán disponibles para ventas</p>
            </div>
        </div>

        <div class="flex items-center gap-4 justify-end mt-8">
            <a href="{{ route('productos.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Cancelar</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none">
                {{ $product->exists ? 'Actualizar' : 'Crear' }} Producto
            </button>
        </div>
    </form>
</div>

<script>
// Toggle variants section based on product type
document.addEventListener('DOMContentLoaded', function() {
    const tipoProductoRadios = document.querySelectorAll('input[name="tipo_producto"]');
    const variantsSection = document.getElementById('variants-section');
    const simpleProductFields = document.getElementById('simple-product-fields');

    function toggleVariantsSection() {
        const selectedTipo = document.querySelector('input[name="tipo_producto"]:checked')?.value;

        if (selectedTipo === 'variable') {
            if (variantsSection) variantsSection.classList.remove('hidden');
            if (simpleProductFields) simpleProductFields.classList.add('hidden');
        } else {
            if (variantsSection) variantsSection.classList.add('hidden');
            if (simpleProductFields) simpleProductFields.classList.remove('hidden');
        }
    }

    tipoProductoRadios.forEach(radio => {
        radio.addEventListener('change', toggleVariantsSection);
    });

    // Initialize on load
    toggleVariantsSection();

    // Toggle attribute values visibility
    const attributeCheckboxes = document.querySelectorAll('.attribute-checkbox');
    attributeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const attributeId = this.dataset.attributeId;
            const valuesDiv = document.getElementById('values-' + attributeId);
            if (valuesDiv) {
                if (this.checked) {
                    valuesDiv.classList.remove('hidden');
                } else {
                    valuesDiv.classList.add('hidden');
                    // Uncheck all values if attribute is unchecked
                    valuesDiv.querySelectorAll('.attribute-value-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                }
            }
        });
    });
});

// Generate variants from selected attributes
function generateVariants() {
    const productCode = document.getElementById('codigo').value || 'PROD';
    const defaultPrice = parseFloat(document.getElementById('precio_venta').value) || 0;

    // Get selected attributes and their values
    const selectedAttributes = [];
    const checkedAttributeCheckboxes = document.querySelectorAll('.attribute-checkbox:checked');
    
    if (checkedAttributeCheckboxes.length === 0) {
        alert('Por favor selecciona primero qué atributos (Color, Talla, etc.) tendrá este producto.');
        return;
    }
    
    let hasError = false;

    checkedAttributeCheckboxes.forEach(attrCheckbox => {
        if (hasError) return;
        
        const attrId = attrCheckbox.dataset.attributeId;
        const attrName = attrCheckbox.dataset.attributeName;
        const selectedValues = [];

        document.querySelectorAll(`.attribute-value-checkbox[data-attribute-id="${attrId}"]:checked`).forEach(valCheckbox => {
            selectedValues.push({
                id: valCheckbox.dataset.valueId,
                name: valCheckbox.dataset.valueName
            });
        });
        
        if (selectedValues.length === 0) {
            alert(`Has seleccionado el atributo "${attrName}" pero no has elegido ningún valor (ej: Rojo, Pequeño) para él.\n\nPor favor selecciona al menos una opción para "${attrName}".`);
            hasError = true;
            return;
        }

        if (selectedValues.length > 0) {
            selectedAttributes.push({
                id: attrId,
                name: attrName,
                values: selectedValues
            });
        }
    });
    
    if (hasError) return;

    if (selectedAttributes.length === 0) {
        alert('Por favor selecciona al menos un atributo con valores.');
        return;
    }

    // Generate cartesian product
    const variants = cartesianProduct(selectedAttributes);

    // Generate SKUs and prepare variant data
    const variantsData = variants.map((variant, index) => {
        const valueSlugs = variant.values.map(v => v.name.substring(0, 3).toUpperCase()).join('-');
        const sku = `${productCode}-${valueSlugs}`;
        const combination = variant.values.map(v => v.name).join(' / ');
        const attributeValueIds = variant.values.map(v => v.id).join(',');

        return {
            sku: sku,
            combination: combination,
            attribute_value_ids: attributeValueIds,
            precio_venta: defaultPrice,
            stock_actual: 0,
            codigo_barras: '',
            activo: true
        };
    });

    // Render variants table
    renderVariantsTable(variantsData);
}

// Calculate cartesian product of attribute values
function cartesianProduct(attributes) {
    if (attributes.length === 0) return [];
    if (attributes.length === 1) {
        return attributes[0].values.map(v => ({ values: [v] }));
    }

    const result = [];
    const first = attributes[0];
    const rest = cartesianProduct(attributes.slice(1));

    first.values.forEach(value => {
        rest.forEach(restCombination => {
            result.push({
                values: [value, ...restCombination.values]
            });
        });
    });

    return result;
}

// Render variants table with editable fields
function renderVariantsTable(variantsData) {
    const container = document.getElementById('variants-table-container');
    const tableDiv = document.getElementById('variants-table');

    if (!container || !tableDiv) return;

    let html = `
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">SKU</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Combinación</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Precio Venta</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Stock Inicial</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Código Barras</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Activo</th>
                </tr>
            </thead>
            <tbody>
    `;

    variantsData.forEach((variant, index) => {
        html += `
            <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                <td class="px-4 py-3.5 text-gray-900 align-middle">
                    <input type="text" name="variants[${index}][sku]" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 min-w-[150px]" value="${variant.sku}" required>
                    <input type="hidden" name="variants[${index}][attribute_value_ids]" value="${variant.attribute_value_ids}">
                </td>
                <td class="px-4 py-3.5 text-gray-900 align-middle"><strong>${variant.combination}</strong></td>
                <td class="px-4 py-3.5 text-gray-900 align-middle">
                    <input type="number" name="variants[${index}][precio_venta]" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 min-w-[100px]" value="${variant.precio_venta}" step="0.01" required>
                </td>
                <td class="px-4 py-3.5 text-gray-900 align-middle">
                    <input type="number" name="variants[${index}][stock_actual]" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 min-w-[80px]" value="${variant.stock_actual}" min="0">
                </td>
                <td class="px-4 py-3.5 text-gray-900 align-middle">
                    <input type="text" name="variants[${index}][codigo_barras]" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 min-w-[120px]" value="${variant.codigo_barras}">
                </td>
                <td class="px-4 py-3.5 text-gray-900 align-middle text-center">
                    <input type="checkbox" name="variants[${index}][activo]" class="w-[18px] h-[18px] accent-indigo-600 cursor-pointer" value="1" ${variant.activo ? 'checked' : ''}>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    tableDiv.innerHTML = html;
    container.classList.remove('hidden');
}
</script>

@endsection
