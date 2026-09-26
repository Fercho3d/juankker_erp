@extends('layouts.app')

@section('content')
<div class="container" style="max-width:680px;margin:2rem auto;padding:0 1rem;">

    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('facturas.index') }}" style="color:#6b7280;font-size:.875rem;text-decoration:none;">← Contabilidad</a>
        <h1 style="font-size:1.5rem;font-weight:700;color:#111827;margin-top:.5rem;">Cargar Factura XML</h1>
        <p style="color:#6b7280;font-size:.875rem;">El XML del SAT se leerá automáticamente. El PDF es opcional.</p>
    </div>

    @if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.25rem;">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('facturas.store') }}" enctype="multipart/form-data"
          style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.5rem;display:flex;flex-direction:column;gap:1.25rem;">
        @csrf

        {{-- Tipo de factura --}}
        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">Tipo de Factura *</label>
            <div style="display:flex;gap:1rem;">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem .9rem;border:2px solid {{ old('tipo_factura') === 'emitida' ? '#16a34a' : '#e5e7eb' }};border-radius:.5rem;flex:1;">
                    <input type="radio" name="tipo_factura" value="emitida" {{ old('tipo_factura', 'emitida') === 'emitida' ? 'checked' : '' }} style="accent-color:#16a34a;">
                    <div>
                        <div style="font-weight:600;color:#166534;">Emitida (Ingreso)</div>
                        <div style="font-size:.75rem;color:#6b7280;">Facturas que yo genero</div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem .9rem;border:2px solid {{ old('tipo_factura') === 'recibida' ? '#d97706' : '#e5e7eb' }};border-radius:.5rem;flex:1;">
                    <input type="radio" name="tipo_factura" value="recibida" {{ old('tipo_factura') === 'recibida' ? 'checked' : '' }} style="accent-color:#d97706;">
                    <div>
                        <div style="font-weight:600;color:#92400e;">Recibida (Egreso)</div>
                        <div style="font-size:.75rem;color:#6b7280;">Facturas de proveedores</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- XML --}}
        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">Archivo XML del SAT *</label>
            <input type="file" name="xml_file" accept=".xml" required
                   style="width:100%;border:2px dashed #d1d5db;border-radius:.5rem;padding:1.5rem;font-size:.875rem;cursor:pointer;">
            <p style="color:#9ca3af;font-size:.75rem;margin-top:.375rem;">Archivo CFDI 3.3 o 4.0 (.xml). Se extraerán todos los datos automáticamente.</p>
        </div>

        {{-- PDF --}}
        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">Archivo PDF (opcional)</label>
            <input type="file" name="pdf_file" accept=".pdf"
                   style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.75rem;font-size:.875rem;">
        </div>

        {{-- Deducible (solo para recibidas) --}}
        <div id="deducible-row" style="display:none;">
            <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
                <input type="checkbox" name="es_deducible" value="1" checked style="width:1.25rem;height:1.25rem;accent-color:#2563eb;">
                <div>
                    <div style="font-weight:600;color:#374151;">Es deducible para ISR / IVA acreditable</div>
                    <div style="font-size:.75rem;color:#6b7280;">Desmarca si el gasto no es deducible fiscalmente</div>
                </div>
            </label>
        </div>

        {{-- Notas --}}
        <div>
            <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;">Notas (opcional)</label>
            <input type="text" name="notas" value="{{ old('notas') }}" maxlength="500"
                   placeholder="Ej: Servicios de diseño enero 2025"
                   style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.5rem .75rem;font-size:.875rem;box-sizing:border-box;">
        </div>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;">
            <a href="{{ route('facturas.index') }}" style="padding:.5rem 1.25rem;border:1px solid #d1d5db;border-radius:.5rem;text-decoration:none;color:#374151;font-size:.875rem;">Cancelar</a>
            <button type="submit" style="background:#16a34a;color:#fff;border:none;padding:.5rem 1.5rem;border-radius:.5rem;cursor:pointer;font-size:.875rem;font-weight:600;">Registrar Factura</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const radios = document.querySelectorAll('input[name="tipo_factura"]');
    const deducibleRow = document.getElementById('deducible-row');
    function toggleDeducible() {
        const val = document.querySelector('input[name="tipo_factura"]:checked')?.value;
        deducibleRow.style.display = val === 'recibida' ? 'block' : 'none';
    }
    radios.forEach(r => r.addEventListener('change', toggleDeducible));
    toggleDeducible();
</script>
@endpush
@endsection
