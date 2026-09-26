@extends('layouts.app')

@section('content')
@php
    $maxIngresos = max(array_map(fn($d) => $d['ingresos'], $meses) ?: [1]);
    $prevAño = $año - 1;
    $nextAño = $año + 1;
@endphp
<div class="container" style="max-width:1100px;margin:2rem auto;padding:0 1rem;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <div>
            <a href="{{ route('facturas.index') }}" style="color:#6b7280;font-size:.8rem;text-decoration:none;">← Contabilidad</a>
            <h1 style="font-size:1.4rem;font-weight:700;color:#111827;margin-top:.2rem;">Reporte Anual</h1>
        </div>
    </div>

    {{-- Navegación de año --}}
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:.75rem 1rem;">
        @if($años->contains($prevAño))
        <a href="{{ route('facturas.reporte-anual', ['año' => $prevAño]) }}"
           style="background:#f3f4f6;border:1px solid #e5e7eb;color:#374151;padding:.4rem .75rem;border-radius:.375rem;text-decoration:none;font-size:.85rem;">‹</a>
        @else
        <span style="background:#f3f4f6;border:1px solid #e5e7eb;color:#d1d5db;padding:.4rem .75rem;border-radius:.375rem;font-size:.85rem;">‹</span>
        @endif

        <form method="GET" style="flex:1;display:flex;justify-content:center;">
            <select name="año" onchange="this.form.submit()"
                    style="border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .75rem;font-size:.9rem;font-weight:600;">
                @foreach($años as $a)
                    <option value="{{ $a }}" {{ $año == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
                @if(!$años->contains(now()->year))
                    <option value="{{ now()->year }}" {{ $año == now()->year ? 'selected' : '' }}>{{ now()->year }}</option>
                @endif
            </select>
        </form>

        @if($nextAño <= now()->year)
        <a href="{{ route('facturas.reporte-anual', ['año' => $nextAño]) }}"
           style="background:#f3f4f6;border:1px solid #e5e7eb;color:#374151;padding:.4rem .75rem;border-radius:.375rem;text-decoration:none;font-size:.85rem;">›</a>
        @else
        <span style="background:#f3f4f6;border:1px solid #e5e7eb;color:#d1d5db;padding:.4rem .75rem;border-radius:.375rem;font-size:.85rem;">›</span>
        @endif

        <span style="font-size:.9rem;font-weight:700;color:#111827;min-width:60px;text-align:center;">{{ $año }}</span>
    </div>

    {{-- Resumen anual --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem;">
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.05em;">Ingresos anuales</div>
            <div style="font-size:1.3rem;font-weight:800;color:#111827;margin:.25rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIngresos,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">Subtotal emitidas</div>
        </div>
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em;">Egresos anuales</div>
            <div style="font-size:1.3rem;font-weight:800;color:#111827;margin:.25rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualEgresos,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">Subtotal deducibles</div>
        </div>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.05em;">IVA total a pagar</div>
            <div style="font-size:1.3rem;font-weight:800;color:{{ $totalAnualIvaPagar > 0 ? '#1d4ed8' : '#16a34a' }};margin:.25rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIvaPagar,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">Cobrado ${{ number_format($totalAnualIvaCobrado,2) }} − Ret. ${{ number_format($totalAnualIvaRet,2) }} − Acred. ${{ number_format($totalAnualIvaAcred,2) }}</div>
        </div>
        <div style="background:#f5f3ff;border:1px solid #e9d5ff;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.05em;">ISR RESICO a pagar</div>
            <div style="font-size:1.3rem;font-weight:800;color:{{ $totalAnualIsrPagar > 0 ? '#7c3aed' : '#16a34a' }};margin:.25rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIsrPagar,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">RESICO ${{ number_format($totalAnualIsrResico,2) }} − Ret. ${{ number_format($totalAnualIsrRet,2) }}</div>
        </div>
    </div>

    {{-- Tabla mensual --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;overflow:hidden;margin-bottom:1rem;">
        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="padding:.65rem 1rem;text-align:left;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Mes</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Ingresos</th>
                    <th style="padding:.65rem .6rem;text-align:left;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;width:80px;"></th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Egresos</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#2563eb;font-weight:600;border-bottom:1px solid #e5e7eb;">IVA Cobr.</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#dc2626;font-weight:600;border-bottom:1px solid #e5e7eb;">IVA Ret.</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">IVA Acred.</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#1d4ed8;font-weight:700;border-bottom:1px solid #e5e7eb;">IVA a Pagar</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#dc2626;font-weight:600;border-bottom:1px solid #e5e7eb;">ISR Ret.</th>
                    <th style="padding:.65rem .6rem;text-align:right;color:#7c3aed;font-weight:700;border-bottom:1px solid #e5e7eb;">ISR a Pagar</th>
                    <th style="padding:.65rem .6rem;text-align:center;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;"></th>
                    <th style="padding:.65rem .6rem;text-align:center;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($meses as $m => $data)
                @php
                    $hayDatos = $data['ingresos'] > 0 || $data['egresos'] > 0;
                    $pct = $maxIngresos > 0 ? round($data['ingresos'] / $maxIngresos * 100) : 0;
                    $esFuturo = ($año == now()->year && $m > now()->month);
                @endphp
                <tr style="border-top:1px solid #f3f4f6;{{ $loop->even ? 'background:#fafafa;' : '' }}{{ !$hayDatos ? 'opacity:.45;' : '' }}">
                    <td style="padding:.55rem 1rem;font-weight:{{ $hayDatos ? '600' : '400' }};color:#374151;">
                        {{ \App\Models\Factura::nombreMes($m) }}
                        @if($esFuturo)<span style="font-size:.6rem;color:#9ca3af;margin-left:.25rem;">·futuro</span>@endif
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right;font-variant-numeric:tabular-nums;color:#16a34a;font-weight:{{ $hayDatos ? '600' : '400' }};">
                        ${{ number_format($data['ingresos'],2) }}
                    </td>
                    {{-- Mini barra de ingreso --}}
                    <td style="padding:.55rem .4rem;">
                        @if($hayDatos)
                        <div style="background:#e5e7eb;border-radius:9999px;height:6px;width:100%;">
                            <div style="background:#16a34a;border-radius:9999px;height:6px;width:{{ $pct }}%;"></div>
                        </div>
                        @endif
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right;font-variant-numeric:tabular-nums;color:#92400e;">${{ number_format($data['egresos'],2) }}</td>
                    <td style="padding:.55rem .6rem;text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($data['ivaCobrado'],2) }}</td>
                    <td style="padding:.55rem .6rem;text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($data['ivaRet'],2) }}</td>
                    <td style="padding:.55rem .6rem;text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($data['ivaAcred'],2) }}</td>
                    <td style="padding:.55rem .6rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;color:{{ $data['ivaPagar'] > 0 ? '#1d4ed8' : '#16a34a' }};">
                        ${{ number_format($data['ivaPagar'],2) }}
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($data['isrRet'],2) }}</td>
                    <td style="padding:.55rem .6rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;color:{{ $data['isrPagar'] > 0 ? '#7c3aed' : '#16a34a' }};">
                        ${{ number_format($data['isrPagar'],2) }}
                    </td>
                    <td style="padding:.55rem .6rem;text-align:center;">
                        @if($hayDatos)
                        <a href="{{ route('facturas.reporte-mensual', ['año' => $año, 'mes' => $m]) }}"
                           style="color:#2563eb;text-decoration:none;font-size:.72rem;font-weight:600;">Ver →</a>
                        @endif
                    </td>
                    <td style="padding:.55rem .6rem;text-align:center;">
                        @php $decl = $declaraciones[$m] ?? null; @endphp
                        @if($hayDatos || $decl)
                        <div style="display:flex;gap:.25rem;justify-content:center;flex-wrap:wrap;">
                            @if($decl?->isPresentada())
                                <span style="background:#dcfce7;color:#166534;padding:.15rem .4rem;border-radius:9999px;font-size:.62rem;font-weight:700;" title="Presentada {{ $decl->fecha_presentacion->format('d/m/Y') }}">✓ Pres.</span>
                            @else
                                @if($decl?->omitida_sat)
                                <span style="background:#fee2e2;color:#991b1b;padding:.15rem .4rem;border-radius:9999px;font-size:.62rem;font-weight:700;" title="{{ $decl->notas }}">Omitida SAT</span>
                                @else
                                <span style="background:#fef3c7;color:#92400e;padding:.15rem .4rem;border-radius:9999px;font-size:.62rem;">Pendiente</span>
                                @endif
                            @endif
                            @if($decl?->isPagada())
                                <span style="background:#dcfce7;color:#166534;padding:.15rem .4rem;border-radius:9999px;font-size:.62rem;font-weight:700;" title="Pagada {{ $decl->fecha_pago->format('d/m/Y') }}">✓ Pagada</span>
                            @else
                                <span style="background:#fee2e2;color:#991b1b;padding:.15rem .4rem;border-radius:9999px;font-size:.62rem;">Sin pago</span>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#f9fafb;border-top:2px solid #e5e7eb;">
                <tr>
                    <td style="padding:.65rem 1rem;font-weight:700;color:#111827;">TOTAL {{ $año }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#16a34a;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIngresos,2) }}</td>
                    <td></td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#92400e;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualEgresos,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIvaCobrado,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIvaRet,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIvaAcred,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#1d4ed8;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIvaPagar,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIsrRet,2) }}</td>
                    <td style="padding:.65rem .6rem;text-align:right;font-weight:700;color:#7c3aed;font-variant-numeric:tabular-nums;">${{ number_format($totalAnualIsrPagar,2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Nota declaración anual --}}
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:.75rem;padding:1rem;font-size:.78rem;color:#713f12;margin-bottom:.75rem;">
        <strong>Declaración Anual (abril {{ $año + 1 }}):</strong>
        Los pagos provisionales de ISR RESICO (${{ number_format($totalAnualIsrPagar,2) }}) son acreditables contra tu ISR anual.
        En abril {{ $año + 1 }} presentas tu declaración anual aplicando la tarifa del Art. 152 LISR sobre
        ingresos acumulados (${{ number_format($totalAnualIngresos,2) }}) y acreditas lo ya pagado mes a mes.
    </div>

    {{-- Panel estado declaración anual --}}
    @php $declAnual = $declaraciones[0] ?? null; @endphp
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
            <h3 style="font-size:.875rem;font-weight:700;color:#374151;margin:0;">Declaración Anual {{ $año }}</h3>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @if($declAnual?->isPresentada())
                    <span style="background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;">✓ Presentada {{ $declAnual->fecha_presentacion->format('d/m/Y') }}</span>
                @else
                    @if($declAnual?->omitida_sat)
                    <span style="background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;" title="{{ $declAnual->notas }}">⚠ Omitida ante el SAT</span>
                    @endif
                    <span style="background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Pendiente de presentar (abril {{ $año + 1 }})</span>
                @endif
                @if($declAnual?->isPagada())
                    <span style="background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;">✓ Pagada {{ $declAnual->fecha_pago->format('d/m/Y') }}</span>
                @else
                    <span style="background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Sin pago registrado</span>
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('facturas.declaracion.guardar') }}">
            @csrf
            <input type="hidden" name="año" value="{{ $año }}">
            {{-- mes vacío = declaración anual --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:.75rem;">
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">ISR anual pagado ($)</label>
                    <input type="number" name="isr_pagado" step="0.01" min="0"
                           value="{{ number_format((float)($declAnual?->isr_pagado ?? 0), 2, '.', '') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">IVA anual pagado ($)</label>
                    <input type="number" name="iva_pagado" step="0.01" min="0"
                           value="{{ number_format((float)($declAnual?->iva_pagado ?? 0), 2, '.', '') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">Fecha presentación</label>
                    <input type="date" name="fecha_presentacion"
                           value="{{ $declAnual?->fecha_presentacion?->format('Y-m-d') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">Fecha pago</label>
                    <input type="date" name="fecha_pago"
                           value="{{ $declAnual?->fecha_pago?->format('Y-m-d') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center;">
                <input type="text" name="notas" placeholder="Notas opcionales"
                       value="{{ $declAnual?->notas }}"
                       style="flex:1;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;">
                <button type="submit"
                        style="background:#374151;color:#fff;border:none;padding:.4rem 1.25rem;border-radius:.375rem;cursor:pointer;font-size:.8rem;white-space:nowrap;">
                    Guardar estado anual
                </button>
            </div>
        </form>
        @if(session('success'))
        <div style="background:#dcfce7;color:#166534;padding:.5rem .75rem;border-radius:.375rem;margin-top:.75rem;font-size:.8rem;">
            {{ session('success') }}
        </div>
        @endif
    </div>

</div>
@endsection
