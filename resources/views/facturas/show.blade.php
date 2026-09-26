@extends('layouts.app')

@section('content')
<div class="container" style="max-width:800px;margin:2rem auto;padding:0 1rem;">

    <div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <a href="{{ route('facturas.index') }}" style="color:#6b7280;font-size:.875rem;text-decoration:none;">← Contabilidad</a>
            <h1 style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.5rem;">Detalle de Factura</h1>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a href="{{ route('facturas.edit', $factura) }}" style="background:#7c3aed;color:#fff;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;font-size:.8rem;">Editar</a>
            <form method="POST" action="{{ route('facturas.destroy', $factura) }}" onsubmit="return confirm('¿Eliminar esta factura?')">
                @csrf @method('DELETE')
                <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:.4rem .9rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;">Eliminar</button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Badge tipo --}}
    <div style="margin-bottom:1.5rem;">
        @if($factura->tipo_factura === 'emitida')
            <span style="background:#dcfce7;color:#166534;padding:.35rem .9rem;border-radius:9999px;font-size:.875rem;font-weight:700;">● Ingreso (Emitida)</span>
        @else
            <span style="background:#fef3c7;color:#92400e;padding:.35rem .9rem;border-radius:9999px;font-size:.875rem;font-weight:700;">● Egreso (Recibida)</span>
            @if(!$factura->es_deducible)
                <span style="background:#fee2e2;color:#991b1b;padding:.35rem .9rem;border-radius:9999px;font-size:.875rem;margin-left:.5rem;">No deducible</span>
            @endif
        @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">

        {{-- Datos fiscales --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;">
            <h2 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f3f4f6;">Datos Fiscales</h2>
            <dl style="display:grid;gap:.6rem;font-size:.8rem;">
                <div><dt style="color:#9ca3af;">UUID (Folio Fiscal)</dt><dd style="font-family:monospace;color:#111827;font-size:.7rem;">{{ $factura->uuid }}</dd></div>
                <div><dt style="color:#9ca3af;">Fecha de Emisión</dt><dd style="color:#111827;">{{ $factura->fecha_emision->format('d/m/Y') }}</dd></div>
                <div><dt style="color:#9ca3af;">Tipo Comprobante</dt><dd style="color:#111827;">{{ ['I'=>'Ingreso','E'=>'Egreso','T'=>'Traslado','N'=>'Nómina','P'=>'Pago'][$factura->tipo_comprobante] ?? $factura->tipo_comprobante }}</dd></div>
                <div><dt style="color:#9ca3af;">Método de Pago</dt><dd style="color:#111827;">{{ $factura->metodo_pago ?? '—' }}</dd></div>
                <div><dt style="color:#9ca3af;">Forma de Pago</dt><dd style="color:#111827;">{{ $factura->forma_pago ?? '—' }}</dd></div>
                <div><dt style="color:#9ca3af;">Uso CFDI</dt><dd style="color:#111827;">{{ $factura->uso_cfdi ?? '—' }}</dd></div>
                <div><dt style="color:#9ca3af;">Moneda</dt><dd style="color:#111827;">{{ $factura->moneda }}{{ $factura->moneda !== 'MXN' ? ' (TC: '.$factura->tipo_cambio.')' : '' }}</dd></div>
                @if($factura->notas)
                <div><dt style="color:#9ca3af;">Notas</dt><dd style="color:#111827;">{{ $factura->notas }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Emisor / Receptor --}}
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;">
                <h2 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:.75rem;">Emisor</h2>
                <p style="font-size:.8rem;font-weight:700;color:#111827;margin-bottom:.25rem;">{{ $factura->rfc_emisor }}</p>
                <p style="font-size:.8rem;color:#6b7280;">{{ $factura->nombre_emisor }}</p>
                @if($factura->regimen_fiscal_emisor)
                    <p style="font-size:.75rem;color:#9ca3af;margin-top:.25rem;">Régimen: {{ $factura->regimen_fiscal_emisor }}</p>
                @endif
            </div>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;">
                <h2 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:.75rem;">Receptor</h2>
                <p style="font-size:.8rem;font-weight:700;color:#111827;margin-bottom:.25rem;">{{ $factura->rfc_receptor }}</p>
                <p style="font-size:.8rem;color:#6b7280;">{{ $factura->nombre_receptor }}</p>
            </div>
        </div>
    </div>

    {{-- Importes --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;margin-top:1rem;">
        <h2 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:1rem;">Importes</h2>
        <table style="width:100%;font-size:.875rem;">
            <tr><td style="padding:.4rem 0;color:#6b7280;">Subtotal</td><td style="text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($factura->subtotal, 2) }}</td></tr>
            @if($factura->descuento > 0)
            <tr><td style="padding:.4rem 0;color:#6b7280;">Descuento</td><td style="text-align:right;color:#dc2626;">-${{ number_format($factura->descuento, 2) }}</td></tr>
            @endif
            <tr><td style="padding:.4rem 0;color:#2563eb;">IVA Trasladado (16%)</td><td style="text-align:right;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($factura->iva_trasladado, 2) }}</td></tr>
            @if($factura->iva_retenido > 0)
            <tr><td style="padding:.4rem 0;color:#dc2626;">IVA Retenido</td><td style="text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">-${{ number_format($factura->iva_retenido, 2) }}</td></tr>
            @endif
            @if($factura->isr_retenido > 0)
            <tr><td style="padding:.4rem 0;color:#dc2626;">ISR Retenido (10%)</td><td style="text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">-${{ number_format($factura->isr_retenido, 2) }}</td></tr>
            @endif
            <tr style="border-top:2px solid #e5e7eb;">
                <td style="padding:.6rem 0;font-weight:700;font-size:1rem;">Total</td>
                <td style="text-align:right;font-weight:700;font-size:1rem;font-variant-numeric:tabular-nums;">${{ number_format($factura->total, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Archivos --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;margin-top:1rem;">
        <h2 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:.75rem;">Archivos</h2>
        <div style="display:flex;gap:.75rem;">
            @if($factura->xml_path)
                <a href="{{ route('facturas.descargar', [$factura, 'xml']) }}" style="background:#f3f4f6;color:#374151;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.8rem;border:1px solid #e5e7eb;">⬇ XML</a>
            @endif
            @if($factura->pdf_path)
                <a href="{{ route('facturas.descargar', [$factura, 'pdf']) }}" style="background:#eff6ff;color:#1d4ed8;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.8rem;border:1px solid #bfdbfe;">⬇ PDF</a>
            @else
                <a href="{{ route('facturas.edit', $factura) }}" style="background:#fefce8;color:#713f12;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.8rem;border:1px solid #fde68a;">+ Subir PDF</a>
            @endif
        </div>
    </div>

</div>
@endsection
