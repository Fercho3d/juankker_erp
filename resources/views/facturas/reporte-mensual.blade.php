@extends('layouts.app')

@section('content')
@php
    $prevMes = $mes == 1 ? 12 : $mes - 1;
    $prevAño = $mes == 1 ? $año - 1 : $año;
    $nextMes = $mes == 12 ? 1 : $mes + 1;
    $nextAño = $mes == 12 ? $año + 1 : $año;
    $esMesActual = ($año == now()->year && $mes == now()->month);
@endphp
<div class="container" style="max-width:960px;margin:2rem auto;padding:0 1rem;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <div>
            <a href="{{ route('facturas.index') }}" style="color:#6b7280;font-size:.8rem;text-decoration:none;">← Contabilidad</a>
            <h1 style="font-size:1.4rem;font-weight:700;color:#111827;margin-top:.2rem;">Reporte Mensual</h1>
        </div>
        <a href="{{ route('facturas.reporte-anual', ['año' => $año]) }}"
           style="background:#f5f3ff;color:#7c3aed;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;font-size:.8rem;font-weight:600;border:1px solid #e9d5ff;">
            Ver Anual {{ $año }} →
        </a>
    </div>

    {{-- Navegación de mes --}}
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:.75rem 1rem;">
        <a href="{{ route('facturas.reporte-mensual', ['año' => $prevAño, 'mes' => $prevMes]) }}"
           style="background:#f3f4f6;border:1px solid #e5e7eb;color:#374151;padding:.4rem .75rem;border-radius:.375rem;text-decoration:none;font-size:.85rem;">‹</a>

        <form method="GET" style="display:flex;gap:.5rem;align-items:center;flex:1;justify-content:center;">
            <select name="año" onchange="this.form.submit()"
                    style="border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .6rem;font-size:.875rem;">
                @foreach($años as $a)
                    <option value="{{ $a }}" {{ $año == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
                @if(!$años->contains(now()->year))
                    <option value="{{ now()->year }}" {{ $año == now()->year ? 'selected' : '' }}>{{ now()->year }}</option>
                @endif
            </select>
            <select name="mes" onchange="this.form.submit()"
                    style="border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .6rem;font-size:.875rem;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>{{ \App\Models\Factura::nombreMes($m) }}</option>
                @endfor
            </select>
        </form>

        @if(!($esMesActual || ($año == now()->year && $mes >= now()->month)))
        <a href="{{ route('facturas.reporte-mensual', ['año' => $nextAño, 'mes' => $nextMes]) }}"
           style="background:#f3f4f6;border:1px solid #e5e7eb;color:#374151;padding:.4rem .75rem;border-radius:.375rem;text-decoration:none;font-size:.85rem;">›</a>
        @else
        <span style="background:#f3f4f6;border:1px solid #e5e7eb;color:#d1d5db;padding:.4rem .75rem;border-radius:.375rem;font-size:.85rem;">›</span>
        @endif

        <span style="font-size:.9rem;font-weight:700;color:#111827;min-width:160px;text-align:center;">
            {{ \App\Models\Factura::nombreMes($mes) }} {{ $año }}
            @if($esMesActual)<span style="font-size:.65rem;background:#fef9c3;color:#713f12;padding:.1rem .4rem;border-radius:9999px;margin-left:.4rem;">En curso</span>@endif
        </span>
    </div>

    @if($emitidas->isEmpty() && $recibidas->isEmpty())
    <div style="background:#f9fafb;border:2px dashed #e5e7eb;border-radius:.75rem;padding:3rem;text-align:center;color:#9ca3af;">
        <div style="font-size:2rem;margin-bottom:.5rem;">📭</div>
        No hay facturas registradas para este período.
    </div>
    @else

    {{-- Cards resumen --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem;">
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.06em;">Ingresos del mes</div>
            <div style="font-size:1.4rem;font-weight:800;color:#111827;margin:.2rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalIngresos,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">{{ $emitidas->count() }} factura(s) emitida(s)</div>
        </div>
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.06em;">Egresos deducibles</div>
            <div style="font-size:1.4rem;font-weight:800;color:#111827;margin:.2rem 0;font-variant-numeric:tabular-nums;">${{ number_format($totalEgresos,2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;">{{ $recibidas->count() }} factura(s) recibida(s)</div>
        </div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.65rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;">Utilidad bruta</div>
            @php $utilidad = $totalIngresos - $totalEgresos; @endphp
            <div style="font-size:1.4rem;font-weight:800;color:{{ $utilidad >= 0 ? '#16a34a' : '#dc2626' }};margin:.2rem 0;font-variant-numeric:tabular-nums;">
                ${{ number_format($utilidad,2) }}
            </div>
            <div style="font-size:.7rem;color:#6b7280;">Ingresos − Egresos</div>
        </div>
    </div>

    {{-- Panel "Para declarar" --}}
    <div style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 100%);border-radius:.75rem;padding:1.25rem;margin-bottom:1rem;color:#fff;">
        <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a5b4fc;margin-bottom:1rem;">
            Para tu declaración mensual — {{ \App\Models\Factura::nombreMes($mes) }} {{ $año }}
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

            {{-- IVA --}}
            <div style="background:rgba(255,255,255,.08);border-radius:.5rem;padding:1rem;">
                <div style="font-size:.7rem;color:#a5b4fc;font-weight:600;margin-bottom:.6rem;">IVA (valor agregado)</div>
                <div style="font-size:.75rem;color:#e0e7ff;margin-bottom:.3rem;display:flex;justify-content:space-between;">
                    <span>IVA cobrado</span><span style="font-variant-numeric:tabular-nums;">+${{ number_format($ivaCobrado,2) }}</span>
                </div>
                <div style="font-size:.75rem;color:#fca5a5;margin-bottom:.3rem;display:flex;justify-content:space-between;">
                    <span>IVA retenido clientes</span><span style="font-variant-numeric:tabular-nums;">−${{ number_format($ivaRetenidoClientes,2) }}</span>
                </div>
                <div style="font-size:.75rem;color:#fca5a5;margin-bottom:.6rem;display:flex;justify-content:space-between;">
                    <span>IVA acreditable (gastos)</span><span style="font-variant-numeric:tabular-nums;">−${{ number_format($ivaAcreditable,2) }}</span>
                </div>
                <div style="border-top:1px solid rgba(255,255,255,.2);padding-top:.5rem;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:700;font-size:.8rem;">IVA A PAGAR</span>
                    <span style="font-size:1.35rem;font-weight:800;font-variant-numeric:tabular-nums;color:{{ $ivaAPagar > 0 ? '#93c5fd' : '#6ee7b7' }};">
                        ${{ number_format($ivaAPagar,2) }}
                    </span>
                </div>
                @if($ivaAPagar <= 0)
                    <div style="font-size:.65rem;color:#6ee7b7;margin-top:.25rem;">✓ Sin cargo (saldo a favor)</div>
                @endif
            </div>

            {{-- ISR --}}
            <div style="background:rgba(255,255,255,.08);border-radius:.5rem;padding:1rem;">
                <div style="font-size:.7rem;color:#a5b4fc;font-weight:600;margin-bottom:.6rem;">ISR RESICO (pago provisional)</div>
                <div style="font-size:.75rem;color:#e0e7ff;margin-bottom:.3rem;display:flex;justify-content:space-between;">
                    <span>Base (ingresos del mes)</span><span style="font-variant-numeric:tabular-nums;">${{ number_format($totalIngresos,2) }}</span>
                </div>
                <div style="font-size:.75rem;color:#e0e7ff;margin-bottom:.3rem;display:flex;justify-content:space-between;">
                    <span>Tasa RESICO</span><span>{{ number_format($tasaResico*100,1) }}%</span>
                </div>
                <div style="font-size:.75rem;color:#e0e7ff;margin-bottom:.3rem;display:flex;justify-content:space-between;">
                    <span>ISR calculado</span><span style="font-variant-numeric:tabular-nums;">${{ number_format($isrResico,2) }}</span>
                </div>
                <div style="font-size:.75rem;color:#fca5a5;margin-bottom:.6rem;display:flex;justify-content:space-between;">
                    <span>ISR retenido clientes</span><span style="font-variant-numeric:tabular-nums;">−${{ number_format($isrRetenidoClientes,2) }}</span>
                </div>
                <div style="border-top:1px solid rgba(255,255,255,.2);padding-top:.5rem;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:700;font-size:.8rem;">ISR A PAGAR</span>
                    <span style="font-size:1.35rem;font-weight:800;font-variant-numeric:tabular-nums;color:{{ $isrAPagar > 0 ? '#c4b5fd' : '#6ee7b7' }};">
                        ${{ number_format($isrAPagar,2) }}
                    </span>
                </div>
                @if($isrAPagar == 0 && $isrRetenidoClientes > 0)
                    <div style="font-size:.65rem;color:#6ee7b7;margin-top:.25rem;">✓ Retenciones cubren el ISR</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tablas --}}
    @if($emitidas->isNotEmpty())
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;margin-bottom:.75rem;overflow:hidden;">
        <div style="padding:.6rem 1rem;background:#f0fdf4;border-bottom:1px solid #bbf7d0;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.825rem;font-weight:700;color:#166534;">Facturas Emitidas — Ingresos</span>
            <span style="font-size:.75rem;color:#6b7280;">{{ $emitidas->count() }} facturas · Subtotal ${{ number_format($emitidas->sum('subtotal'),2) }}</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="padding:.5rem 1rem;text-align:left;color:#6b7280;font-weight:600;">Fecha</th>
                    <th style="padding:.5rem .75rem;text-align:left;color:#6b7280;font-weight:600;">RFC / Receptor</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-weight:600;">Subtotal</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#2563eb;font-weight:600;">IVA</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#dc2626;font-weight:600;">IVA Ret.</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#dc2626;font-weight:600;">ISR Ret.</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#374151;font-weight:600;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($emitidas as $f)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:.5rem 1rem;color:#6b7280;">{{ $f->fecha_emision->format('d/m') }}</td>
                    <td style="padding:.5rem .75rem;">
                        <a href="{{ route('facturas.show',$f) }}" style="color:#2563eb;text-decoration:none;font-weight:600;">{{ $f->rfc_receptor }}</a>
                        <div style="color:#9ca3af;font-size:.68rem;">{{ Str::limit($f->nombre_receptor,28) }}</div>
                    </td>
                    <td style="padding:.5rem .75rem;text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($f->subtotal,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($f->iva_trasladado,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($f->iva_retenido,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($f->isr_retenido,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:600;font-variant-numeric:tabular-nums;">${{ number_format($f->total,2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#f0fdf4;border-top:2px solid #bbf7d0;">
                <tr>
                    <td colspan="2" style="padding:.5rem 1rem;font-weight:700;color:#166534;font-size:.78rem;">TOTAL</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($emitidas->sum('subtotal'),2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($ivaCobrado,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($ivaRetenidoClientes,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($isrRetenidoClientes,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($emitidas->sum('total'),2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if($recibidas->isNotEmpty())
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;overflow:hidden;">
        <div style="padding:.6rem 1rem;background:#fffbeb;border-bottom:1px solid #fde68a;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.825rem;font-weight:700;color:#92400e;">Facturas Recibidas Deducibles — Egresos</span>
            <span style="font-size:.75rem;color:#6b7280;">{{ $recibidas->count() }} facturas · IVA acreditable ${{ number_format($ivaAcreditable,2) }}</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="padding:.5rem 1rem;text-align:left;color:#6b7280;font-weight:600;">Fecha</th>
                    <th style="padding:.5rem .75rem;text-align:left;color:#6b7280;font-weight:600;">RFC / Emisor</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-weight:600;">Subtotal</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#2563eb;font-weight:600;">IVA Acreditable</th>
                    <th style="padding:.5rem .75rem;text-align:right;color:#374151;font-weight:600;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recibidas as $f)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:.5rem 1rem;color:#6b7280;">{{ $f->fecha_emision->format('d/m') }}</td>
                    <td style="padding:.5rem .75rem;">
                        <a href="{{ route('facturas.show',$f) }}" style="color:#2563eb;text-decoration:none;font-weight:600;">{{ $f->rfc_emisor }}</a>
                        <div style="color:#9ca3af;font-size:.68rem;">{{ Str::limit($f->nombre_emisor,28) }}</div>
                    </td>
                    <td style="padding:.5rem .75rem;text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($f->subtotal,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($f->iva_trasladado,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:600;font-variant-numeric:tabular-nums;">${{ number_format($f->total,2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#fffbeb;border-top:2px solid #fde68a;">
                <tr>
                    <td colspan="2" style="padding:.5rem 1rem;font-weight:700;color:#92400e;font-size:.78rem;">TOTAL</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($totalEgresos,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($ivaAcreditable,2) }}</td>
                    <td style="padding:.5rem .75rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($recibidas->sum('total'),2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Panel estado declaración --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;margin-top:.75rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
            <h3 style="font-size:.875rem;font-weight:700;color:#374151;margin:0;">Estado de la declaración — {{ \App\Models\Factura::nombreMes($mes) }} {{ $año }}</h3>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @if($declaracion?->isPresentada())
                    <span style="background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;">✓ Presentada {{ $declaracion->fecha_presentacion->format('d/m/Y') }}</span>
                @else
                    @if($declaracion?->omitida_sat)
                    <span style="background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;" title="{{ $declaracion->notas }}">⚠ Omitida ante el SAT</span>
                    @endif
                    <span style="background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Pendiente de presentar</span>
                @endif
                @if($declaracion?->isPagada())
                    <span style="background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700;">✓ Pagada {{ $declaracion->fecha_pago->format('d/m/Y') }}</span>
                @else
                    <span style="background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Pendiente de pago</span>
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('facturas.declaracion.guardar') }}">
            @csrf
            <input type="hidden" name="año" value="{{ $año }}">
            <input type="hidden" name="mes"  value="{{ $mes }}">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:.75rem;">
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">IVA pagado ($)</label>
                    <input type="number" name="iva_pagado" step="0.01" min="0"
                           value="{{ number_format((float)($declaracion?->iva_pagado ?? $ivaAPagar), 2, '.', '') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">ISR pagado ($)</label>
                    <input type="number" name="isr_pagado" step="0.01" min="0"
                           value="{{ number_format((float)($declaracion?->isr_pagado ?? $isrAPagar), 2, '.', '') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">Fecha presentación</label>
                    <input type="date" name="fecha_presentacion"
                           value="{{ $declaracion?->fecha_presentacion?->format('Y-m-d') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#374151;display:block;margin-bottom:.25rem;font-weight:600;">Fecha pago</label>
                    <input type="date" name="fecha_pago"
                           value="{{ $declaracion?->fecha_pago?->format('Y-m-d') }}"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center;">
                <input type="text" name="notas" placeholder="Notas opcionales"
                       value="{{ $declaracion?->notas }}"
                       style="flex:1;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .5rem;font-size:.8rem;">
                <button type="submit"
                        style="background:#374151;color:#fff;border:none;padding:.4rem 1.25rem;border-radius:.375rem;cursor:pointer;font-size:.8rem;white-space:nowrap;">
                    Guardar estado
                </button>
            </div>
        </form>
        @if(session('success'))
        <div style="background:#dcfce7;color:#166534;padding:.5rem .75rem;border-radius:.375rem;margin-top:.75rem;font-size:.8rem;">
            {{ session('success') }}
        </div>
        @endif
    </div>

    @endif
</div>
@endsection
