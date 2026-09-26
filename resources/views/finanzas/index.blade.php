@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .viz { --serie-1: #2a78d6; --serie-2: #eb6834; --serie-3: #1baf7a; --rejilla: #e7e5e4; --tinta: #52514e; }
        html.dark .viz { --serie-1: #3987e5; --serie-2: #d95926; --serie-3: #199e70; --rejilla: #3a3a38; --tinta: #c3c2b7; }
    </style>
@endpush

@php
    $mesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $dinero = fn ($n) => ($n < 0 ? '-$' : '$').number_format(abs($n));
    $ingresos = $meses->sum('ingresos');
    $gastos = $meses->sum('gastos');
    $deducibles = $meses->sum('deducibles');
    $boton = fn ($activo) => 'px-4 py-2 rounded-xl text-sm font-semibold border transition-colors '
        .($activo ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400');
    $maxTipo = max(1, $porTipo->max('total') ?? 1);
@endphp

@section('content')
<div class="viz w-full px-4 sm:px-6 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Resumen {{ $año }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Ingresos, gastos y ganancia con tus facturas del SAT (sin IVA).</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($años->sort() as $a)
                <a href="{{ route('finanzas.index', ['año' => $a]) }}" class="{{ $boton($a == $año) }}">{{ $a }}</a>
            @endforeach
        </div>
    </div>

    {{-- Totales del año --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Ingresos</div>
            <div class="text-2xl font-bold text-gray-900">{{ $dinero($ingresos) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Gastos facturados</div>
            <div class="text-2xl font-bold text-gray-900">{{ $dinero($gastos) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Ganancia</div>
            <div class="text-2xl font-bold text-gray-900">{{ $dinero($ingresos - $gastos) }}</div>
            <div class="text-xs text-gray-500">{{ $ingresos ? round(($ingresos - $gastos) / $ingresos * 100) : 0 }}% de lo que facturaste</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Gastos que bajan tu ISR mensual</div>
            <div class="text-2xl font-bold text-gray-900">{{ $dinero($deducibles) }}</div>
            <div class="text-xs text-gray-500">{{ $ingresos ? round($deducibles / $ingresos * 100) : 0 }}% de tus ingresos</div>
        </div>
    </div>

    {{-- Por mes --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Por mes</h2>
            <div class="flex items-center gap-4 text-xs text-gray-600">
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:var(--serie-1)"></span>Ingresos</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:var(--serie-2)"></span>Gastos</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-4 h-0.5" style="background:var(--serie-3)"></span>Ganancia</span>
            </div>
        </div>
        <div class="relative h-72"><canvas id="grafica-meses" aria-label="Ingresos, gastos y ganancia por mes de {{ $año }}" role="img"></canvas></div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer text-gray-500">Ver como tabla</summary>
            <table class="w-full mt-2 tabular-nums">
                <thead class="text-xs text-gray-500"><tr><th class="text-left py-1">Mes</th><th class="text-right">Ingresos</th><th class="text-right">Gastos</th><th class="text-right">Deducibles</th><th class="text-right">Ganancia</th></tr></thead>
                <tbody>
                    @foreach($meses as $m)
                        <tr class="border-t border-gray-100"><td class="py-1">{{ $mesesCortos[$m['mes'] - 1] }}</td><td class="text-right">{{ $dinero($m['ingresos']) }}</td><td class="text-right">{{ $dinero($m['gastos']) }}</td><td class="text-right">{{ $dinero($m['deducibles']) }}</td><td class="text-right">{{ $dinero($m['ganancia']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        {{-- Gastos por tipo --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Gastos por tipo</h2>
            @forelse(\App\Http\Controllers\FinanzasController::TIPOS as $clave => $nombre)
                @php $t = $porTipo[$clave] ?? ['total' => 0, 'n' => 0]; @endphp
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-800">{{ $clave === 'sin_efectos' && $t['total'] ? '⚠ ' : '' }}{{ $nombre }}</span>
                        <span class="tabular-nums font-semibold text-gray-900">{{ $dinero($t['total']) }} <span class="text-xs font-normal text-gray-500">· {{ $t['n'] }} facturas</span></span>
                    </div>
                    <div class="h-2.5 rounded bg-gray-100"><div class="h-2.5 rounded" style="width: {{ max(0, $t['total']) / $maxTipo * 100 }}%; background: var(--serie-2)"></div></div>
                </div>
            @empty
            @endforelse
            <p class="text-xs text-gray-500">El tipo sale del «uso del CFDI» con que te facturaron. Pide siempre <strong>G03</strong> (gastos en general) en lo que compres para tu trabajo; con <strong>S01</strong> no puedes deducirlo.</p>
        </div>

        {{-- Clientes --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">A quién le facturaste</h2>
            <table class="w-full text-sm">
                @forelse($clientes as $c)
                    <tr class="border-t border-gray-100">
                        <td class="py-2 pr-2 text-gray-800">{{ $c->nombre }}<div class="text-xs text-gray-400">{{ $c->rfc }} · {{ $c->n }} facturas</div></td>
                        <td class="py-2 text-right tabular-nums font-semibold text-gray-900 whitespace-nowrap">{{ $dinero($c->total) }}</td>
                        <td class="py-2 pl-3 text-right text-xs text-gray-500 w-12">{{ $ingresos ? round($c->total / $ingresos * 100) : 0 }}%</td>
                    </tr>
                @empty
                    <tr><td class="py-6 text-center text-gray-400">Sin facturas emitidas en {{ $año }}.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    {{-- Proveedores --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">En qué gastas (10 proveedores principales)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500"><tr><th class="text-left py-1">Proveedor</th><th class="text-left">Uso del CFDI</th><th class="text-right">Facturas</th><th class="text-right">Total</th></tr></thead>
                @forelse($proveedores as $p)
                    @php $usos = collect(explode(',', (string) $p->usos))->filter(); @endphp
                    <tr class="border-t border-gray-100">
                        <td class="py-2 pr-2 text-gray-800">{{ $p->nombre }}<div class="text-xs text-gray-400">{{ $p->rfc }}</div></td>
                        <td class="py-2 pr-2">
                            @foreach($usos as $u)
                                <span class="inline-block px-2 py-0.5 text-xs border rounded-full mr-1 {{ $u === 'S01' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                    {{ $u === 'S01' ? '⚠ ' : '' }}{{ $u }} · {{ \App\Http\Controllers\FinanzasController::nombreUso($u) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="py-2 text-right tabular-nums text-gray-600">{{ $p->n }}</td>
                        <td class="py-2 text-right tabular-nums font-semibold text-gray-900 whitespace-nowrap">{{ $dinero($p->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Sin facturas recibidas en {{ $año }}.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const css = getComputedStyle(document.querySelector('.viz'));
        const color = v => css.getPropertyValue(v).trim();
        const meses = @json($meses->values());
        const pesos = n => (n < 0 ? '-$' : '$') + Math.abs(n).toLocaleString('es-MX');
        new Chart(document.getElementById('grafica-meses'), {
            data: {
                labels: @json($mesesCortos),
                datasets: [
                    { type: 'bar', label: 'Ingresos', data: meses.map(m => m.ingresos), backgroundColor: color('--serie-1'), borderRadius: 4, maxBarThickness: 22, order: 2 },
                    { type: 'bar', label: 'Gastos', data: meses.map(m => m.gastos), backgroundColor: color('--serie-2'), borderRadius: 4, maxBarThickness: 22, order: 2 },
                    { type: 'line', label: 'Ganancia', data: meses.map(m => m.ganancia), borderColor: color('--serie-3'), backgroundColor: color('--serie-3'), borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, tension: 0.25, order: 1 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ` ${c.dataset.label}: ${pesos(c.parsed.y)}` } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: color('--tinta') } },
                    y: { grid: { color: color('--rejilla') }, border: { display: false }, ticks: { color: color('--tinta'), callback: v => pesos(v) } },
                },
            },
        });
    })();
</script>
@endpush
