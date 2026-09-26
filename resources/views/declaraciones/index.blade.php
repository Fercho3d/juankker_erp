@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
    $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombre = fn ($p) => $p['mes'] ? $meses[$p['mes']].' '.$p['año'] : 'Anual '.$p['año'];
    $dinero = fn ($n) => ($n < 0 ? '-$' : '$').number_format(abs($n), 2);
    $badges = [
        'presentada' => ['Presentada', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'omitida' => ['Omitida SAT', 'bg-red-50 text-red-700 border-red-200'],
        'por_presentar' => ['Por presentar', 'bg-amber-50 text-amber-700 border-amber-200'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Declaraciones</h1>
            <p class="text-sm text-gray-500 mt-0.5">Todos tus meses y anuales. Preséntalos en orden, del más antiguo al más reciente.</p>
        </div>
        <a href="{{ route('sat.index') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:border-gray-400">Descarga SAT</a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">{{ $errors->first() }}</div>
    @endif

    {{-- Resumen --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Omitidas ante el SAT</div>
            <div class="text-2xl font-bold text-red-600">{{ $conteo['omitida'] ?? 0 }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Por presentar</div>
            <div class="text-2xl font-bold text-amber-600">{{ $conteo['por_presentar'] ?? 0 }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Presentadas</div>
            <div class="text-2xl font-bold text-emerald-600">{{ $conteo['presentada'] ?? 0 }}</div>
        </div>
        <div class="bg-indigo-600 text-white rounded-xl p-4">
            <div class="text-xs text-indigo-100">Sigue</div>
            @if($siguiente)
                <div class="text-lg font-bold">{{ $nombre($siguiente) }}</div>
                <a href="#p-{{ $siguiente['año'] }}-{{ $siguiente['mes'] ?? 0 }}" class="text-xs underline text-indigo-100">Ir a la fila</a>
            @else
                <div class="text-lg font-bold">¡Al corriente!</div>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
        <select name="año" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm bg-white">
            <option value="">Todos los años</option>
            @foreach($años as $a)
                <option value="{{ $a }}" @selected(request('año') == $a)>{{ $a }}</option>
            @endforeach
        </select>
        <select name="estado" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm bg-white">
            <option value="">Todas</option>
            <option value="pendientes" @selected(request('estado') === 'pendientes')>Sólo pendientes</option>
        </select>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Periodo</th>
                    <th class="text-right px-3 py-3">Ingresos</th>
                    <th class="text-right px-3 py-3">Gastos</th>
                    <th class="text-right px-3 py-3">Utilidad</th>
                    <th class="text-right px-3 py-3" title="Positivo: a pagar. Negativo: a favor.">IVA</th>
                    <th class="text-left px-3 py-3">Estado</th>
                    <th class="text-left px-3 py-3">Archivos</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($periodos as $p)
                    @php
                        $d = $p['declaracion'];
                        [$etiqueta, $clase] = $badges[$p['estado']];
                        $esSiguiente = $siguiente && $siguiente['año'] === $p['año'] && $siguiente['mes'] === $p['mes'];
                        $datos = [
                            'año' => $p['año'], 'mes' => $p['mes'], 'titulo' => $nombre($p),
                            'fecha_presentacion' => $d?->fecha_presentacion?->format('Y-m-d'), 'fecha_pago' => $d?->fecha_pago?->format('Y-m-d'),
                            'iva_pagado' => $d?->iva_pagado, 'isr_pagado' => $d?->isr_pagado, 'notas' => $d?->notas,
                        ];
                    @endphp
                    <tr id="p-{{ $p['año'] }}-{{ $p['mes'] ?? 0 }}"
                        class="border-t border-gray-100 {{ $p['mes'] ? '' : 'bg-indigo-50/60 font-semibold' }} {{ $esSiguiente ? 'ring-2 ring-inset ring-indigo-400' : '' }}">
                        <td class="px-4 py-2.5 whitespace-nowrap text-gray-900">{{ $nombre($p) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums text-emerald-700">{{ $dinero($p['ingresos']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums text-amber-700">{{ $dinero($p['gastos']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $dinero($p['ingresos'] - $p['gastos']) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums {{ $p['iva'] < 0 ? 'text-emerald-700' : 'text-gray-900' }}">{{ $dinero($p['iva']) }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 text-xs font-semibold border rounded-full {{ $clase }}" title="{{ $d?->notas }}">
                                {{ $etiqueta }}{{ $d?->fecha_presentacion ? ' '.$d->fecha_presentacion->format('d/m/Y') : '' }}
                            </span>
                            @if($d?->isPagada())
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold border rounded-full bg-emerald-50 text-emerald-700 border-emerald-200">Pagada</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap text-xs">
                            @if($d?->acuse_path)
                                <a href="{{ route('declaraciones.archivo', [$d, 'acuse']) }}" target="_blank" class="text-indigo-600 hover:underline">Acuse</a>
                            @endif
                            @if($d?->pago_path)
                                <a href="{{ route('declaraciones.archivo', [$d, 'pago']) }}" target="_blank" class="text-indigo-600 hover:underline ml-2">Pago</a>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap text-right">
                            <a href="{{ $p['mes'] ? route('facturas.reporte-mensual', ['año' => $p['año'], 'mes' => $p['mes']]) : route('facturas.reporte-anual', ['año' => $p['año']]) }}"
                               class="text-xs text-gray-500 hover:text-gray-900 mr-2">Detalle</a>
                            <button type="button"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $p['estado'] === 'presentada' ? 'border border-gray-200 text-gray-600' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}"
                                    onclick='abrirDeclaracion(@json($datos))'>
                                {{ $p['estado'] === 'presentada' ? 'Editar' : 'Registrar' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">No hay periodos con este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400 mt-3">Montos calculados con tus facturas del SAT (sin complementos de pago; las notas de crédito restan). El ISR depende de tu régimen: revísalo en el detalle o con tu contador.</p>
</div>

{{-- Registrar presentación y pago --}}
<dialog id="dlg-declaracion" class="rounded-2xl p-0 w-full max-w-lg backdrop:bg-black/40">
    <form method="POST" action="{{ route('declaraciones.guardar', ['volver' => request()->getQueryString()]) }}" enctype="multipart/form-data" class="p-6 space-y-4">
        @csrf
        <input type="hidden" name="año">
        <input type="hidden" name="mes">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900" data-titulo></h2>
            <button type="button" onclick="this.closest('dialog').close()" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>
        <label class="block">
            <span class="text-sm font-medium text-gray-700">Acuse de la declaración (PDF o imagen)</span>
            <input type="file" name="acuse" accept=".pdf,image/*" class="mt-1 block w-full text-sm">
            <span class="text-xs text-gray-400">Al subirlo se marca como presentada.</span>
        </label>
        <label class="block">
            <span class="text-sm font-medium text-gray-700">Comprobante de pago (opcional)</span>
            <input type="file" name="pago" accept=".pdf,image/*" class="mt-1 block w-full text-sm">
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="block"><span class="text-sm text-gray-700">Fecha presentación</span>
                <input type="date" name="fecha_presentacion" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></label>
            <label class="block"><span class="text-sm text-gray-700">Fecha pago</span>
                <input type="date" name="fecha_pago" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></label>
            <label class="block"><span class="text-sm text-gray-700">ISR pagado</span>
                <input type="number" step="0.01" min="0" name="isr_pagado" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></label>
            <label class="block"><span class="text-sm text-gray-700">IVA pagado</span>
                <input type="number" step="0.01" min="0" name="iva_pagado" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></label>
        </div>
        <label class="block"><span class="text-sm text-gray-700">Notas</span>
            <input type="text" name="notas" maxlength="300" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></label>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 border border-gray-200 rounded-xl text-sm">Cancelar</button>
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700">Guardar</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
    function abrirDeclaracion(d) {
        const dlg = document.getElementById('dlg-declaracion');
        const f = dlg.querySelector('form');
        f.reset();
        dlg.querySelector('[data-titulo]').textContent = d.titulo;
        for (const k of ['año', 'mes', 'fecha_presentacion', 'fecha_pago', 'iva_pagado', 'isr_pagado', 'notas']) {
            f.elements[k].value = d[k] ?? '';
        }
        dlg.showModal();
    }
</script>
@endpush
