@extends('layouts.app')

@section('content')
<div class="container" style="max-width:1300px;margin:2rem auto;padding:0 1rem;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#111827;">Contabilidad SAT</h1>
            <p style="color:#6b7280;font-size:.875rem;">
                Facturas emitidas y recibidas
                &nbsp;·&nbsp;
                <span style="color:#16a34a;font-size:.75rem;font-weight:600;">✓ Los UUIDs son únicos — no se permiten duplicados</span>
            </p>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:flex-end;">
            <a href="{{ route('sat.index') }}" style="background:#f3f4f6;color:#374151;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.875rem;border:1px solid #e5e7eb;">⬇ Descarga SAT</a>
            <a href="{{ route('facturas.reporte-mensual') }}" style="background:#2563eb;color:#fff;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.875rem;">Reporte Mensual</a>
            <a href="{{ route('facturas.reporte-anual') }}" style="background:#7c3aed;color:#fff;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.875rem;">Reporte Anual</a>
            <a href="{{ route('facturas.create') }}" style="background:#16a34a;color:#fff;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.875rem;">+ Cargar XML</a>
        </div>
    </div>

    {{-- Resumen general (todos los años) --}}
    @if($resumen['totalIngresos'] > 0)
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;margin-bottom:1.5rem;">

        {{-- Ingresos --}}
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:#16a34a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">Total Facturado</div>
            <div style="font-size:1.15rem;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">${{ number_format($resumen['totalIngresos'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">Ingresos acumulados</div>
        </div>

        {{-- Egresos --}}
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">Egresos Deducibles</div>
            <div style="font-size:1.15rem;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">${{ number_format($resumen['totalEgresos'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">Gastos acumulados</div>
        </div>

        {{-- IVA a pagar --}}
        <div style="background:{{ $resumen['ivaAPagar'] > 0 ? '#eff6ff' : '#f0fdf4' }};border:1px solid {{ $resumen['ivaAPagar'] > 0 ? '#93c5fd' : '#86efac' }};border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:{{ $resumen['ivaAPagar'] > 0 ? '#1d4ed8' : '#16a34a' }};text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">IVA Neto a Pagar</div>
            <div style="font-size:1.15rem;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">${{ number_format($resumen['ivaAPagar'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">
                Cobrado ${{ number_format($resumen['ivaCobrado'],2) }}
                − Ret. ${{ number_format($resumen['ivaRetenidoClientes'],2) }}
                − Acred. ${{ number_format($resumen['ivaAcreditable'],2) }}
            </div>
        </div>

        {{-- ISR RESICO estimado --}}
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">ISR RESICO Estimado</div>
            <div style="font-size:1.15rem;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">${{ number_format($resumen['isrResicoTotal'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">Calculado mes a mes sobre ingresos</div>
        </div>

        {{-- ISR ya retenido --}}
        <div style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:#6d28d9;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">ISR Retenido (pagado)</div>
            <div style="font-size:1.15rem;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">${{ number_format($resumen['isrRetenidoClientes'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">Retenido por clientes personas morales</div>
        </div>

        {{-- ISR pendiente --}}
        <div style="background:{{ $resumen['isrAPagar'] > 0 ? '#fff7ed' : '#f0fdf4' }};border:2px solid {{ $resumen['isrAPagar'] > 0 ? '#fb923c' : '#86efac' }};border-radius:.75rem;padding:1rem;">
            <div style="font-size:.7rem;font-weight:600;color:{{ $resumen['isrAPagar'] > 0 ? '#c2410c' : '#16a34a' }};text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">ISR Pendiente</div>
            <div style="font-size:1.25rem;font-weight:800;color:{{ $resumen['isrAPagar'] > 0 ? '#c2410c' : '#16a34a' }};font-variant-numeric:tabular-nums;">${{ number_format($resumen['isrAPagar'],2) }}</div>
            <div style="font-size:.7rem;color:#6b7280;margin-top:.15rem;">RESICO − retenido por clientes</div>
        </div>

    </div>
    @endif

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:center;">
        <select name="año" style="border:1px solid #d1d5db;border-radius:.375rem;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Todos los años</option>
            @foreach($años as $a)
                <option value="{{ $a }}" {{ request('año') == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <select name="mes" style="border:1px solid #d1d5db;border-radius:.375rem;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Todos los meses</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ request('mes') == $m ? 'selected' : '' }}>{{ \App\Models\Factura::nombreMes($m) }}</option>
            @endfor
        </select>
        <select name="tipo" style="border:1px solid #d1d5db;border-radius:.375rem;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Emitidas y Recibidas</option>
            <option value="emitida"  {{ request('tipo') === 'emitida'  ? 'selected' : '' }}>Emitidas (Ingresos)</option>
            <option value="recibida" {{ request('tipo') === 'recibida' ? 'selected' : '' }}>Recibidas (Egresos)</option>
        </select>
        <select name="archivos" style="border:1px solid #d1d5db;border-radius:.375rem;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Todos los archivos</option>
            <option value="con_xml"  {{ request('archivos') === 'con_xml'  ? 'selected' : '' }}>Con XML</option>
            <option value="con_pdf"  {{ request('archivos') === 'con_pdf'  ? 'selected' : '' }}>Con PDF</option>
            <option value="sin_pdf"  {{ request('archivos') === 'sin_pdf'  ? 'selected' : '' }}>Sin PDF</option>
        </select>
        <button type="submit" style="background:#374151;color:#fff;border:none;padding:.375rem 1rem;border-radius:.375rem;cursor:pointer;font-size:.875rem;">Filtrar</button>
        <a href="{{ route('facturas.index') }}" style="color:#6b7280;padding:.375rem .5rem;font-size:.875rem;text-decoration:none;">Limpiar</a>
        <span style="margin-left:auto;font-size:.8rem;color:#9ca3af;">{{ $facturas->total() }} facturas</span>
    </form>

    {{-- Tabla --}}
    <div style="background:#fff;border-radius:.75rem;border:1px solid #e5e7eb;overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="padding:.6rem 1rem;text-align:left;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Folio / Fecha</th>
                    <th style="padding:.6rem .5rem;text-align:left;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Tipo</th>
                    <th style="padding:.6rem .5rem;text-align:left;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">RFC / Razón Social</th>
                    <th style="padding:.6rem .5rem;text-align:right;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Subtotal</th>
                    <th style="padding:.6rem .5rem;text-align:right;color:#2563eb;font-weight:600;border-bottom:1px solid #e5e7eb;">IVA</th>
                    <th style="padding:.6rem .5rem;text-align:right;color:#dc2626;font-weight:600;border-bottom:1px solid #e5e7eb;">ISR Ret.</th>
                    <th style="padding:.6rem .5rem;text-align:right;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Total</th>
                    <th style="padding:.6rem .5rem;text-align:center;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Archivos</th>
                    <th style="padding:.6rem .5rem;text-align:center;color:#374151;font-weight:600;border-bottom:1px solid #e5e7eb;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $f)
                <tr style="border-bottom:1px solid #f3f4f6;{{ $loop->even ? 'background:#fafafa;' : '' }}">
                    <td style="padding:.6rem 1rem;">
                        <div style="font-family:monospace;font-size:.7rem;color:#9ca3af;" title="{{ $f->uuid }}">{{ substr($f->uuid, 0, 16) }}…</div>
                        <div style="font-size:.8rem;color:#374151;font-weight:500;">{{ $f->fecha_emision->format('d/m/Y') }}</div>
                    </td>
                    <td style="padding:.6rem .5rem;">
                        @if($f->tipo_factura === 'emitida')
                            <span style="background:#dcfce7;color:#166534;padding:.2rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Ingreso</span>
                        @else
                            <span style="background:#fef3c7;color:#92400e;padding:.2rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:600;">Egreso</span>
                        @endif
                    </td>
                    <td style="padding:.6rem .5rem;">
                        @if($f->tipo_factura === 'emitida')
                            <div style="font-size:.8rem;font-weight:600;color:#111827;">{{ $f->rfc_receptor }}</div>
                            <div style="font-size:.7rem;color:#6b7280;">{{ Str::limit($f->nombre_receptor, 28) }}</div>
                        @else
                            <div style="font-size:.8rem;font-weight:600;color:#111827;">{{ $f->rfc_emisor }}</div>
                            <div style="font-size:.7rem;color:#6b7280;">{{ Str::limit($f->nombre_emisor, 28) }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem .5rem;text-align:right;font-variant-numeric:tabular-nums;">${{ number_format($f->subtotal, 2) }}</td>
                    <td style="padding:.6rem .5rem;text-align:right;color:#2563eb;font-variant-numeric:tabular-nums;">${{ number_format($f->iva_trasladado, 2) }}</td>
                    <td style="padding:.6rem .5rem;text-align:right;color:#dc2626;font-variant-numeric:tabular-nums;">${{ number_format($f->isr_retenido, 2) }}</td>
                    <td style="padding:.6rem .5rem;text-align:right;font-weight:600;font-variant-numeric:tabular-nums;">${{ number_format($f->total, 2) }}</td>

                    {{-- Archivos --}}
                    <td style="padding:.6rem .5rem;text-align:center;">
                        <div style="display:flex;gap:.3rem;justify-content:center;flex-wrap:wrap;">
                            @if($f->xml_path)
                                <button onclick="verXML('{{ route('facturas.visualizar', [$f, 'xml']) }}', '{{ substr($f->uuid,0,8) }}')"
                                        title="Ver XML"
                                        style="background:#f0fdf4;border:1px solid #86efac;color:#16a34a;padding:.2rem .45rem;border-radius:.3rem;font-size:.7rem;font-weight:700;cursor:pointer;">
                                    XML
                                </button>
                                <a href="{{ route('facturas.descargar', [$f, 'xml']) }}"
                                   title="Descargar XML"
                                   style="background:#f9fafb;border:1px solid #d1d5db;color:#6b7280;padding:.2rem .35rem;border-radius:.3rem;text-decoration:none;font-size:.65rem;">
                                    ⬇
                                </a>
                            @else
                                <span style="color:#d1d5db;font-size:.7rem;">sin XML</span>
                            @endif

                            @if($f->pdf_path)
                                <button onclick="verPDF('{{ route('facturas.visualizar', [$f, 'pdf']) }}', '{{ substr($f->uuid,0,8) }}')"
                                        title="Ver PDF"
                                        style="background:#eff6ff;border:1px solid #93c5fd;color:#1d4ed8;padding:.2rem .45rem;border-radius:.3rem;font-size:.7rem;font-weight:700;cursor:pointer;">
                                    PDF
                                </button>
                                <a href="{{ route('facturas.descargar', [$f, 'pdf']) }}"
                                   title="Descargar PDF"
                                   style="background:#f9fafb;border:1px solid #d1d5db;color:#6b7280;padding:.2rem .35rem;border-radius:.3rem;text-decoration:none;font-size:.65rem;">
                                    ⬇
                                </a>
                            @else
                                <button onclick="abrirSubirPDF({{ $f->id }}, '{{ route('facturas.update', $f) }}')"
                                        title="Subir PDF"
                                        style="background:#fefce8;border:1px solid #fde68a;color:#92400e;padding:.2rem .45rem;border-radius:.3rem;font-size:.7rem;cursor:pointer;">
                                    ↑ PDF
                                </button>
                            @endif
                        </div>
                    </td>

                    {{-- Acciones --}}
                    <td style="padding:.6rem .5rem;text-align:center;">
                        <div style="display:flex;gap:.4rem;justify-content:center;">
                            <a href="{{ route('facturas.show', $f) }}" style="color:#2563eb;text-decoration:none;font-size:.75rem;">Ver</a>
                            <a href="{{ route('facturas.edit', $f) }}" style="color:#7c3aed;text-decoration:none;font-size:.75rem;">Editar</a>
                            <form method="POST" action="{{ route('facturas.destroy', $f) }}" onsubmit="return confirm('¿Eliminar esta factura?')" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:.75rem;padding:0;">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="padding:3rem;text-align:center;color:#9ca3af;">
                        No hay facturas registradas.<br>
                        <a href="{{ route('sat.index') }}" style="color:#7c3aed;">Descarga masiva desde el SAT →</a>
                        &nbsp;o&nbsp;
                        <a href="{{ route('facturas.create') }}" style="color:#16a34a;">carga un XML manualmente</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Paginación --}}
    @if($facturas->hasPages())
    <div style="display:flex;justify-content:center;align-items:center;gap:.4rem;margin-top:1rem;font-size:.8rem;">
        @if($facturas->onFirstPage())
            <span style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #e5e7eb;color:#d1d5db;">‹</span>
        @else
            <a href="{{ $facturas->previousPageUrl() }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">‹</a>
        @endif

        @foreach($facturas->getUrlRange(max(1,$facturas->currentPage()-2), min($facturas->lastPage(),$facturas->currentPage()+2)) as $page => $url)
            @if($page == $facturas->currentPage())
                <span style="padding:.3rem .6rem;border-radius:.375rem;background:#2563eb;color:#fff;font-weight:600;">{{ $page }}</span>
            @else
                <a href="{{ $url }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">{{ $page }}</a>
            @endif
        @endforeach

        @if($facturas->hasMorePages())
            <a href="{{ $facturas->nextPageUrl() }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">›</a>
        @else
            <span style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #e5e7eb;color:#d1d5db;">›</span>
        @endif

        <span style="color:#9ca3af;margin-left:.25rem;">Página {{ $facturas->currentPage() }} de {{ $facturas->lastPage() }}</span>
    </div>
    @endif

    {{-- Explorador de archivos por año --}}
    @if($facturas->total() > 0)
    <div style="margin-top:2rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#374151;margin-bottom:.75rem;">Explorador de Archivos por Año</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem;">
            @foreach($años as $a)
            @php
                $countAño = \App\Models\Factura::where('user_id', auth()->id())->where('año', $a)->count();
                $conXml   = \App\Models\Factura::where('user_id', auth()->id())->where('año', $a)->whereNotNull('xml_path')->count();
                $conPdf   = \App\Models\Factura::where('user_id', auth()->id())->where('año', $a)->whereNotNull('pdf_path')->count();
            @endphp
            @if($countAño > 0)
            <a href="{{ route('facturas.index', ['año' => $a]) }}"
               style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1rem;text-decoration:none;display:block;transition:border-color .15s;"
               onmouseover="this.style.borderColor='#7c3aed'" onmouseout="this.style.borderColor='#e5e7eb'">
                <div style="font-size:1.25rem;font-weight:700;color:#111827;">{{ $a }}</div>
                <div style="font-size:.75rem;color:#6b7280;margin-top:.25rem;">{{ $countAño }} factura(s)</div>
                <div style="display:flex;gap:.3rem;margin-top:.5rem;">
                    @if($conXml > 0)
                    <span style="background:#f0fdf4;border:1px solid #86efac;color:#16a34a;padding:.15rem .4rem;border-radius:.25rem;font-size:.65rem;font-weight:700;">
                        XML {{ $conXml }}
                    </span>
                    @endif
                    @if($conPdf > 0)
                    <span style="background:#eff6ff;border:1px solid #93c5fd;color:#1d4ed8;padding:.15rem .4rem;border-radius:.25rem;font-size:.65rem;font-weight:700;">
                        PDF {{ $conPdf }}
                    </span>
                    @endif
                </div>
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- Modal subir PDF --}}
<div id="upload-pdf-modal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:.75rem;width:90%;max-width:420px;box-shadow:0 25px 60px rgba(0,0,0,.4);padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0;">Subir PDF</h3>
            <button onclick="cerrarSubirPDF()" style="background:none;border:none;color:#6b7280;cursor:pointer;font-size:1.2rem;line-height:1;">✕</button>
        </div>
        <form id="upload-pdf-form" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.875rem;color:#374151;margin-bottom:.375rem;">Archivo PDF</label>
                <input type="file" name="pdf_file" accept="application/pdf"
                       style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.375rem .5rem;font-size:.875rem;">
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                <button type="button" onclick="cerrarSubirPDF()"
                        style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;padding:.5rem 1rem;border-radius:.375rem;cursor:pointer;font-size:.875rem;">
                    Cancelar
                </button>
                <button type="submit"
                        style="background:#2563eb;color:#fff;border:none;padding:.5rem 1rem;border-radius:.375rem;cursor:pointer;font-size:.875rem;">
                    Subir
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal visor PDF --}}
<div id="pdf-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border-radius:.75rem;width:92%;max-width:960px;height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.5);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #313244;flex-shrink:0;">
            <span id="pdf-modal-title" style="font-family:monospace;font-size:.8rem;color:#cdd6f4;font-weight:600;"></span>
            <button onclick="cerrarPDF()" style="background:none;border:none;color:#6c7086;cursor:pointer;font-size:1.2rem;line-height:1;padding:.25rem .5rem;border-radius:.3rem;" onmouseover="this.style.color='#f38ba8'" onmouseout="this.style.color='#6c7086'">✕</button>
        </div>
        <iframe id="pdf-modal-frame" src="" style="flex:1;border:none;border-radius:0 0 .75rem .75rem;background:#fff;"></iframe>
    </div>
</div>

{{-- Modal visor XML --}}
<div id="xml-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border-radius:.75rem;width:90%;max-width:860px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.5);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #313244;flex-shrink:0;">
            <span id="xml-modal-title" style="font-family:monospace;font-size:.8rem;color:#cdd6f4;font-weight:600;"></span>
            <button onclick="cerrarXML()" style="background:none;border:none;color:#6c7086;cursor:pointer;font-size:1.2rem;line-height:1;padding:.25rem .5rem;border-radius:.3rem;" onmouseover="this.style.color='#f38ba8'" onmouseout="this.style.color='#6c7086'">✕</button>
        </div>
        <pre id="xml-modal-content" style="margin:0;padding:1rem;overflow:auto;font-family:monospace;font-size:.75rem;line-height:1.6;color:#cdd6f4;white-space:pre;flex:1;"></pre>
    </div>
</div>

@push('scripts')
<script>
function abrirSubirPDF(id, action) {
    document.getElementById('upload-pdf-form').action = action;
    document.getElementById('upload-pdf-modal').style.display = 'flex';
}

function cerrarSubirPDF() {
    document.getElementById('upload-pdf-modal').style.display = 'none';
    document.getElementById('upload-pdf-form').reset();
}

document.getElementById('upload-pdf-modal').addEventListener('click', function(e) {
    if (e.target === this) cerrarSubirPDF();
});

function verPDF(url, uuid) {
    const modal = document.getElementById('pdf-modal');
    document.getElementById('pdf-modal-title').textContent = uuid + '.pdf';
    document.getElementById('pdf-modal-frame').src = url;
    modal.style.display = 'flex';
}

function cerrarPDF() {
    document.getElementById('pdf-modal').style.display = 'none';
    document.getElementById('pdf-modal-frame').src = '';
}

document.getElementById('pdf-modal').addEventListener('click', function(e) {
    if (e.target === this) cerrarPDF();
});

function verXML(url, uuid) {
    const modal   = document.getElementById('xml-modal');
    const content = document.getElementById('xml-modal-content');
    const title   = document.getElementById('xml-modal-title');

    title.textContent   = 'Cargando…';
    content.innerHTML   = '';
    modal.style.display = 'flex';

    fetch(url)
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
        .then(xml => {
            title.textContent = uuid + '.xml';
            content.innerHTML = colorearXML(formatearXML(xml));
        })
        .catch(e => {
            title.textContent = 'Error';
            content.textContent = 'No se pudo cargar el XML: ' + e.message;
        });
}

function cerrarXML() {
    document.getElementById('xml-modal').style.display = 'none';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarXML(); cerrarPDF(); cerrarSubirPDF(); }
});

document.getElementById('xml-modal').addEventListener('click', function(e) {
    if (e.target === this) cerrarXML();
});

function formatearXML(xml) {
    let formatted = '', indent = 0;
    xml = xml.replace(/>\s*</g, '><').trim();
    xml.split(/(<[^>]+>)/).forEach(node => {
        if (!node) return;
        if (/^<\//.test(node))        indent = Math.max(0, indent - 1);
        if (node.startsWith('<'))     formatted += '  '.repeat(indent) + node + '\n';
        else if (node.trim())         formatted += '  '.repeat(indent) + node.trim() + '\n';
        if (/^<[^\/!?][^>]*[^\/]>$/.test(node) && !/^<[^>]+\/>$/.test(node)) indent++;
    });
    return formatted.trim();
}

function colorearXML(str) {
    return str
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/(&lt;\/?)([\w:]+)/g, '$1<span style="color:#89b4fa">$2</span>')
        .replace(/([\w:]+)(=)(&quot;[^&]*&quot;|"[^"]*")/g,
            '<span style="color:#fab387">$1</span>$2<span style="color:#a6e3a1">$3</span>')
        .replace(/(&lt;\?[^&]*\?&gt;)/g, '<span style="color:#6c7086">$1</span>')
        .replace(/(&lt;!--[\s\S]*?--&gt;)/g, '<span style="color:#6c7086">$1</span>');
}
</script>
@endpush
</div>
@endsection
