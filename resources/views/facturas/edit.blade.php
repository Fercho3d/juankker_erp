@extends('layouts.app')

@section('content')
<div class="container" style="max-width:600px;margin:2rem auto;padding:0 1rem;">

    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('facturas.show', $factura) }}" style="color:#6b7280;font-size:.875rem;text-decoration:none;">← Ver Factura</a>
        <h1 style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.5rem;">Editar Factura</h1>
        <p style="font-family:monospace;font-size:.75rem;color:#9ca3af;">{{ $factura->uuid }}</p>
    </div>

    @if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
        @foreach($errors->all() as $e) <p style="margin:.2rem 0;">{{ $e }}</p> @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('facturas.update', $factura) }}" enctype="multipart/form-data"
          style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.5rem;display:flex;flex-direction:column;gap:1.25rem;">
        @csrf @method('PUT')

        {{-- Info de solo lectura --}}
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:.5rem;padding:1rem;font-size:.8rem;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.5rem;">
                <div><span style="color:#9ca3af;">Fecha:</span> {{ $factura->fecha_emision->format('d/m/Y') }}</div>
                <div><span style="color:#9ca3af;">Tipo:</span> {{ $factura->tipo_factura }}</div>
                <div><span style="color:#9ca3af;">Total:</span> ${{ number_format($factura->total, 2) }}</div>
                <div><span style="color:#9ca3af;">ISR Ret:</span> ${{ number_format($factura->isr_retenido, 2) }}</div>
            </div>
            <p style="color:#9ca3af;font-size:.75rem;margin-top:.5rem;">Los datos fiscales del XML no se pueden modificar.</p>
        </div>

        @if($factura->tipo_factura === 'recibida')
        <div>
            <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;padding:.75rem;border:1px solid #e5e7eb;border-radius:.5rem;">
                <input type="checkbox" name="es_deducible" value="1" {{ $factura->es_deducible ? 'checked' : '' }}
                       style="width:1.25rem;height:1.25rem;accent-color:#2563eb;">
                <div>
                    <div style="font-weight:600;color:#374151;">Es deducible (IVA acreditable / ISR deducible)</div>
                    <div style="font-size:.75rem;color:#6b7280;">Desactiva si este gasto no es deducible fiscalmente</div>
                </div>
            </label>
        </div>
        @endif

        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">PDF (reemplazar o subir)</label>
            @if($factura->pdf_path)
                <p style="font-size:.8rem;color:#16a34a;margin-bottom:.5rem;">✓ Ya tiene PDF. Sube uno nuevo para reemplazarlo.</p>
            @endif
            <input type="file" name="pdf_file" accept=".pdf"
                   style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.75rem;font-size:.875rem;">
        </div>

        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">Notas</label>
            <input type="text" name="notas" value="{{ old('notas', $factura->notas) }}" maxlength="500"
                   style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.5rem .75rem;font-size:.875rem;box-sizing:border-box;">
        </div>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;">
            <a href="{{ route('facturas.show', $factura) }}" style="padding:.5rem 1.25rem;border:1px solid #d1d5db;border-radius:.5rem;text-decoration:none;color:#374151;font-size:.875rem;">Cancelar</a>
            <button type="submit" style="background:#7c3aed;color:#fff;border:none;padding:.5rem 1.5rem;border-radius:.5rem;cursor:pointer;font-size:.875rem;font-weight:600;">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection
