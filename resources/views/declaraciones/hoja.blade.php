@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
    $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $titulo = fn ($x) => $meses[$x['mes']].' '.$x['año'];
    $rango = $h['mes'] === 1 ? 'enero' : 'enero a '.mb_strtolower($meses[$h['mes']]);
    $presentada = $declaracion?->isPresentada();

    // [campo del SAT, valor, qué hacer, ayuda]; valor null = dejar vacío
    $isr = [
        ['Ingresos del periodo', $h['ingresos_periodo'], 'captura', 'Lo que cobraste en '.mb_strtolower($meses[$h['mes']]).' (subtotal de tus facturas, sin IVA).'],
        ['Total de ingresos', $h['ingresos_acumulados'], 'revisa', "Acumulado de $rango. El SAT suma los meses anteriores que ya presentaste."],
        ['Ingresos exentos', null, 'vacío', ''],
        ['Total de ingresos acumulables', $h['ingresos_acumulados'], 'revisa', ''],
        ['Compras y gastos del periodo', $h['gastos_periodo'], 'captura', 'Gastos deducibles del mes (subtotal, sin IVA).'],
        ['Total de compras y gastos', $h['gastos_acumulados'], 'revisa', "Acumulado de $rango."],
        ['Deducción de inversiones de ejercicios anteriores', null, 'vacío', 'Sólo si compraste equipo en años anteriores y lo deduces por partes.'],
        ['¿Tienes facilidades administrativas y estímulos deducibles?', 'No', 'captura', 'Son para el campo, pesca y autotransporte; no aplican a tu actividad.'],
        ['Participación de los trabajadores en las utilidades', 0, 'captura', 'Sólo si tienes empleados y les pagaste PTU.'],
        ['Pérdidas fiscales de ejercicios anteriores', null, 'vacío', 'Sólo si tu declaración anual de un año anterior dio pérdida.'],
        ['Base gravable del pago provisional', $h['base_gravable'], 'revisa', 'Total de ingresos acumulables menos total de compras y gastos.'],
        ['ISR causado', $h['isr_causado'], 'revisa', 'Tarifa del art. 96 ('.$h['tarifa'].') acumulada a '.$h['mes'].' '.($h['mes'] === 1 ? 'mes' : 'meses').'.'],
        ['Pagos provisionales efectuados con anterioridad', $h['pagos_anteriores'], 'captura', 'Suma del ISR que declaraste de enero al mes anterior.'],
        ['ISR retenido del periodo', $h['isr_retenido_periodo'] ?: null, $h['isr_retenido_periodo'] ? 'captura' : 'vacío', 'Lo que te retuvieron tus clientes personas morales este mes.'],
        ['Impuesto retenido', $h['isr_retenido_acumulado'], 'revisa', "Retenciones acumuladas de $rango."],
        ['ISR a cargo', $h['isr_cargo'], 'pagar', 'Lo que pagas de ISR este mes.'],
    ];
    $iva = [
        ['Actos o actividades gravados a la tasa del 16%', $h['iva_actos_16'], 'captura', 'Mismo monto que tus ingresos del periodo.'],
        ['Actos o actividades gravados a la tasa del 0%', null, 'vacío', ''],
        ['Actos o actividades exentos', null, 'vacío', ''],
        ['IVA cobrado del periodo a la tasa del 16%', $h['iva_trasladado'], 'revisa', 'El 16% de tus ingresos del periodo.'],
        ['IVA acreditable del periodo', $h['iva_acreditable'], 'captura', 'El IVA de tus gastos deducibles del mes.'],
        ['IVA retenido', $h['iva_retenido'] ?: null, $h['iva_retenido'] ? 'captura' : 'vacío', 'El IVA que te retuvieron tus clientes personas morales.'],
        [$h['iva_neto'] < 0 ? 'Saldo a favor del periodo' : 'Cantidad a cargo', abs($h['iva_neto']), 'revisa', $h['iva_neto'] < 0 ? 'Te queda a favor: lo aplicas en los meses siguientes.' : ''],
        ['Acreditamiento de saldos a favor de periodos anteriores', $h['iva_acredita_saldo'] ?: null, $h['iva_acredita_saldo'] ? 'captura' : 'vacío',
            $h['iva_saldo_anterior'] ? 'Tienes $'.number_format($h['iva_saldo_anterior']).' a favor de meses anteriores.' : ''],
        ['Impuesto a cargo', $h['iva_cargo'], 'pagar', 'Lo que pagas de IVA este mes.'],
    ];
    $etiquetas = [
        'captura' => ['Escribe', 'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'revisa' => ['Lo calcula el SAT: compara', 'bg-gray-50 text-gray-600 border-gray-200'],
        'vacío' => ['Déjalo vacío', 'bg-white text-gray-400 border-gray-200'],
        'pagar' => ['A pagar', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    ];
@endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('declaraciones.index') }}#p-{{ $h['año'] }}-{{ $h['mes'] }}" class="text-sm text-gray-500 hover:text-gray-800">← Declaraciones</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Hoja SAT · {{ $titulo($h) }}</h1>
            <p class="text-sm text-gray-500">Llénala en el portal del SAT en este orden. Da clic en una cantidad para copiarla.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($anterior)
                <a href="{{ route('declaraciones.hoja', ['año' => $anterior['año'], 'mes' => $anterior['mes']]) }}" class="px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:border-gray-400">← {{ $titulo($anterior) }}</a>
            @endif
            @if($siguiente)
                <a href="{{ route('declaraciones.hoja', ['año' => $siguiente['año'], 'mes' => $siguiente['mes']]) }}" class="px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:border-gray-400">{{ $titulo($siguiente) }} →</a>
            @endif
        </div>
    </div>

    @if($presentada)
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            Presentada el {{ $declaracion->fecha_presentacion->format('d/m/Y') }} (ISR ${{ number_format($declaracion->isr_pagado) }}, IVA ${{ number_format($declaracion->iva_pagado) }}).
        </div>
    @endif

    @if($h['año'] <= 2024 && ! $presentada)
        <div class="px-4 py-3 mb-5 text-sm text-indigo-900 bg-indigo-50 border border-indigo-200 rounded-xl">
            <strong>Regularización Fiscal 2026: no pagues recargos de este mes.</strong>
            En «Determinación de pago», a «¿Tienes estímulos por aplicar?» responde <strong>Sí</strong>, elige
            <strong>«Estímulo de regularización fiscal»</strong> y captura como monto los recargos (y la multa, si la hay).
            Pagas sólo el impuesto actualizado. Aplica a 2024 y anteriores, a más tardar el 31 de diciembre de 2026
            (art. Vigésimo Segundo Transitorio LIF 2026, regla 9.2.5 RMF).
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">ISR a pagar</div>
            <div class="text-2xl font-bold text-gray-900">${{ number_format($h['isr_cargo']) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">IVA a pagar</div>
            <div class="text-2xl font-bold text-gray-900">${{ number_format($h['iva_cargo']) }}</div>
        </div>
    </div>

    @foreach(['ISR personas físicas, actividad empresarial y profesional' => $isr, 'IVA simplificado' => $iva] as $seccion => $campos)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $seccion }}</div>
            <table class="w-full text-sm">
                @foreach($campos as [$campo, $valor, $accion, $ayuda])
                    @php [$etiqueta, $clase] = $etiquetas[$accion]; @endphp
                    <tr class="border-t border-gray-100 {{ $accion === 'pagar' ? 'bg-emerald-50/50 font-semibold' : '' }}">
                        <td class="px-5 py-2.5 text-gray-900 w-1/2">
                            {{ $campo }}
                            @if($ayuda)<div class="text-xs text-gray-400 font-normal">{{ $ayuda }}</div>@endif
                        </td>
                        <td class="px-3 py-2.5 text-right tabular-nums text-base whitespace-nowrap">
                            @if($valor === null)
                                <span class="text-gray-300">—</span>
                            @else
                                <button type="button" class="copiar px-2 py-0.5 rounded hover:bg-indigo-50" data-valor="{{ is_numeric($valor) ? (int) round($valor) : $valor }}">
                                    {{ is_numeric($valor) ? number_format($valor) : $valor }}
                                </button>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 text-xs border rounded-full {{ $clase }}">{{ $etiqueta }}</span>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div class="text-xs text-gray-500 space-y-1">
        <p>Cantidades en pesos sin centavos, como las pide el SAT. Calculadas con tus facturas descargadas del SAT y con lo que ya registraste como presentado.</p>
        <p>Si «ISR causado» o «Total de ingresos» no coinciden con lo que calcula el portal, revisa que los meses anteriores de {{ $h['año'] }} ya estén presentados con sus montos correctos.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.copiar').forEach(b => b.addEventListener('click', () => {
        navigator.clipboard?.writeText(b.dataset.valor);
        const antes = b.textContent;
        b.textContent = 'Copiado';
        setTimeout(() => b.textContent = antes, 900);
    }));
</script>
@endpush
