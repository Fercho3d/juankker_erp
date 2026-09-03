@extends('layouts.superadmin')
@section('title', 'Planes')

@section('content')
    {{-- Visibilidad global --}}
    <form method="POST" action="{{ route('superadmin.settings.update') }}" class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 mb-6 flex flex-wrap items-center gap-6">
        @csrf @method('PUT')
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_plans" value="1" @checked($showPlans)> Mostrar página de planes</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_prices" value="1" @checked($showPrices)> Mostrar precios</label>
        <button class="px-4 py-2 rounded-lg bg-navy text-white text-sm font-semibold">Guardar</button>
    </form>

    {{-- Lista de planes --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto mb-8">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3">Plan</th><th class="px-5 py-3">Precio</th>
                    <th class="px-5 py-3">Usuarios</th><th class="px-5 py-3">Sucursales</th>
                    <th class="px-5 py-3">Productos</th><th class="px-5 py-3">Estado</th><th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($plans as $p)
                    <tr class="{{ $p->trashed() ? 'opacity-40' : '' }}">
                        <td class="px-5 py-3 font-medium">{{ $p->name }} <span class="text-xs text-slate-400">({{ $p->key }})</span></td>
                        <td class="px-5 py-3">${{ number_format((float) $p->price, 2) }}</td>
                        <td class="px-5 py-3">{{ $p->max_users }}</td>
                        <td class="px-5 py-3">{{ $p->max_branches }}</td>
                        <td class="px-5 py-3">{{ $p->max_products }}</td>
                        <td class="px-5 py-3">{{ $p->activo ? 'Activo' : 'Inactivo' }} · {{ $p->visible ? 'Visible' : 'Oculto' }}</td>
                        <td class="px-5 py-3 text-right">
                            @unless($p->trashed())
                                <form method="POST" action="{{ route('superadmin.planes.destroy', $p) }}" onsubmit="return confirm('¿Desactivar plan?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 text-xs hover:underline">Desactivar</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Crear plan --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 max-w-3xl">
        <h2 class="font-semibold text-navy mb-4">Nuevo plan</h2>
        <form method="POST" action="{{ route('superadmin.planes.store') }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            <label class="text-sm">Clave <input name="key" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="pro"></label>
            <label class="text-sm">Nombre <input name="name" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="Profesional"></label>
            <label class="text-sm">Tagline <input name="tagline" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Precio mensual <input name="price" type="number" step="0.01" value="0" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Precio anual <input name="precio_anual" type="number" step="0.01" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Orden <input name="orden" type="number" value="0" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Máx. usuarios <input name="max_users" type="number" value="5" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Máx. sucursales <input name="max_branches" type="number" value="1" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Máx. productos <input name="max_products" type="number" value="500" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Almacenamiento (GB) <input name="max_storage_gb" type="number" value="5" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm md:col-span-2">Módulos (uno por línea) <textarea name="modules" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="finanzas&#10;cfdi&#10;inventario"></textarea></label>
            <label class="text-sm md:col-span-2">Features / bullets (uno por línea) <textarea name="features" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></textarea></label>
            <div class="md:col-span-2 flex flex-wrap gap-5 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="destacado" value="1"> Destacado</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="activo" value="1" checked> Activo</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="visible" value="1" checked> Visible</label>
            </div>
            <div class="md:col-span-2"><button class="px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-semibold">Crear plan</button></div>
        </form>
    </div>
@endsection
