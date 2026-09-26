@extends('layouts.app')

@section('content')
<div class="container" style="max-width:900px;margin:2rem auto;padding:0 1rem;">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#111827;">Descarga Masiva SAT</h1>
            <p style="color:#6b7280;font-size:.875rem;">Descarga automática de CFDIs con tu e.firma</p>
        </div>
        <a href="{{ route('facturas.index') }}" style="color:#6b7280;font-size:.875rem;text-decoration:none;">← Contabilidad</a>
    </div>

    {{-- Alertas --}}
    @foreach(['success'=>'#dcfce7:#bbf7d0:#166534','info'=>'#dbeafe:#bfdbfe:#1e40af','error'=>'#fee2e2:#fecaca:#991b1b'] as $tipo => $colores)
    @if(session($tipo))
    @php [$bg,$border,$color] = explode(':', $colores); @endphp
    <div style="background:{{ $bg }};border:1px solid {{ $border }};color:{{ $color }};padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
        {{ session($tipo) }}
    </div>
    @endif
    @endforeach

    @if($errors->has('general'))
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
        {{ $errors->first('general') }}
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BLOQUE e.firma                                             --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    @if($credencial)
    {{-- e.firma ya guardada --}}
    <div style="background:#f0fdf4;border:2px solid #86efac;border-radius:.75rem;padding:1.25rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <p style="font-weight:700;color:#166534;margin-bottom:.25rem;">
                ✓ e.firma guardada — {{ $credencial->rfc }}
            </p>
            <p style="font-size:.8rem;color:#4b7c59;">
                {{ $credencial->nombre }} &nbsp;·&nbsp;
                Vigente hasta: <strong>{{ $credencial->vigencia?->format('d/m/Y') ?? 'Sin fecha' }}</strong>
                @if(!$credencial->estaVigente())
                    &nbsp;<span style="color:#dc2626;font-weight:700;">⚠ VENCIDA</span>
                @endif
            </p>
        </div>
        <div style="display:flex;gap:.5rem;flex-direction:column;align-items:flex-end;">
            <button onclick="document.getElementById('form-actualizar').style.display=document.getElementById('form-actualizar').style.display==='none'?'block':'none'"
                    style="background:#fff;border:1px solid #86efac;color:#166534;padding:.35rem .8rem;border-radius:.375rem;cursor:pointer;font-size:.75rem;">
                Actualizar contraseña / archivos
            </button>
            <form method="POST" action="{{ route('sat.credencial.eliminar') }}"
                  onsubmit="return confirm('¿Eliminar la e.firma guardada?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit" style="background:#fff;border:1px solid #fca5a5;color:#dc2626;padding:.35rem .8rem;border-radius:.375rem;cursor:pointer;font-size:.75rem;">
                    Eliminar del servidor
                </button>
            </form>
        </div>
    </div>

    {{-- Formulario actualizar (oculto) --}}
    <div id="form-actualizar" style="display:none;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;margin-bottom:1.5rem;">
        <h3 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:1rem;">Actualizar e.firma</h3>
        <form method="POST" action="{{ route('sat.credencial.guardar') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-bottom:.75rem;">
                <div>
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Nuevo .cer (opcional)</label>
                    <input type="file" name="cer_file" accept=".cer" style="width:100%;font-size:.8rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Nuevo .key (opcional)</label>
                    <input type="file" name="key_file" accept=".key" style="width:100%;font-size:.8rem;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Contraseña *</label>
                    <input type="password" name="key_password" required autocomplete="off"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.8rem;box-sizing:border-box;">
                </div>
            </div>
            <button type="submit" style="background:#16a34a;color:#fff;border:none;padding:.4rem 1rem;border-radius:.375rem;cursor:pointer;font-size:.8rem;">Guardar cambios</button>
        </form>
    </div>

    @else
    {{-- Sin e.firma guardada --}}
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:.75rem;padding:1.25rem;margin-bottom:1.5rem;">
        <h3 style="font-size:.9rem;font-weight:700;color:#713f12;margin-bottom:.75rem;">Guardar e.firma (recomendado)</h3>
        <p style="font-size:.8rem;color:#713f12;margin-bottom:.75rem;">
            Guarda tu e.firma para no tener que subirla cada vez. Se almacena cifrada en el servidor.
        </p>
        <form method="POST" action="{{ route('sat.credencial.guardar') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-bottom:.75rem;">
                <div>
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Certificado .cer *</label>
                    <input type="file" name="cer_file" accept=".cer" required style="width:100%;font-size:.8rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Llave .key *</label>
                    <input type="file" name="key_file" accept=".key" required style="width:100%;font-size:.8rem;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Contraseña de la llave *</label>
                    <input type="password" name="key_password" required autocomplete="off"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.8rem;box-sizing:border-box;">
                </div>
            </div>
            <button type="submit" style="background:#d97706;color:#fff;border:none;padding:.4rem 1.25rem;border-radius:.375rem;cursor:pointer;font-size:.8rem;font-weight:600;">
                Guardar e.firma →
            </button>
        </form>
        @if($errors->has('cer_file') || $errors->has('key_password'))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.6rem .75rem;border-radius:.375rem;margin-top:.75rem;font-size:.8rem;">
            {{ $errors->first('cer_file') }} {{ $errors->first('key_password') }}
        </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- NUEVA SOLICITUD                                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.5rem;margin-bottom:2rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#374151;margin-bottom:1.25rem;">Nueva solicitud de descarga</h2>

        @if($errors->has('general') || $errors->hasAny(['cer_file','key_file','key_password']))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;font-size:.875rem;">
            {{ $errors->first('cer_file') ?: $errors->first('key_file') ?: $errors->first('key_password') ?: $errors->first('general') }}
        </div>
        @endif

        <form method="POST" action="{{ route('sat.solicitar') }}" enctype="multipart/form-data">
            @csrf

            {{-- Tipo --}}
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;font-size:.875rem;">Tipo de facturas *</label>
                <div style="display:flex;gap:1rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem 1rem;border:2px solid #e5e7eb;border-radius:.5rem;flex:1;font-size:.875rem;">
                        <input type="radio" name="tipo_factura" value="emitida" {{ old('tipo_factura','emitida')==='emitida'?'checked':'' }} style="accent-color:#16a34a;">
                        <span><strong style="color:#166534;">Emitidas</strong> — mis ingresos</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem 1rem;border:2px solid #e5e7eb;border-radius:.5rem;flex:1;font-size:.875rem;">
                        <input type="radio" name="tipo_factura" value="recibida" {{ old('tipo_factura')==='recibida'?'checked':'' }} style="accent-color:#d97706;">
                        <span><strong style="color:#92400e;">Recibidas</strong> — mis gastos</span>
                    </label>
                </div>
            </div>

            {{-- Selector período: año o rango --}}
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:.5rem;font-size:.875rem;">Período *</label>
                <div style="display:flex;gap:1rem;margin-bottom:.75rem;">
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.875rem;">
                        <input type="radio" name="modo_fecha" value="año" id="modo-año"
                               {{ old('modo_fecha','año')==='año'?'checked':'' }} style="accent-color:#7c3aed;">
                        Por año completo
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.875rem;">
                        <input type="radio" name="modo_fecha" value="rango" id="modo-rango"
                               {{ old('modo_fecha')==='rango'?'checked':'' }} style="accent-color:#7c3aed;">
                        Por rango de fechas
                    </label>
                </div>

                {{-- Selector año --}}
                <div id="panel-año" style="display:none;">
                    <select name="año"
                            style="border:1px solid #d1d5db;border-radius:.375rem;padding:.5rem .75rem;font-size:.875rem;width:200px;">
                        @foreach($añosDisponibles as $a)
                            <option value="{{ $a }}" {{ old('año', now()->year) == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:.75rem;color:#9ca3af;margin-top:.35rem;">Descarga todo el año de una sola vez (enero–diciembre).</p>
                </div>

                {{-- Selector rango --}}
                <div id="panel-rango" style="display:none;">
                    <div style="display:flex;gap:.75rem;">
                        <div>
                            <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Fecha inicio</label>
                            <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}"
                                   style="border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.875rem;">
                        </div>
                        <div>
                            <label style="font-size:.8rem;color:#374151;display:block;margin-bottom:.25rem;">Fecha fin</label>
                            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}"
                                   style="border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.875rem;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- e.firma: si no hay guardada, pedirla; si hay, mostrar opcional --}}
            @if(!$credencial)
            <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:.5rem;padding:1rem;margin-bottom:1rem;">
                <p style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.75rem;">e.firma (FIEL) *</p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-bottom:.75rem;">
                    <div>
                        <label style="font-size:.75rem;color:#374151;display:block;margin-bottom:.25rem;">Certificado .cer</label>
                        <input type="file" name="cer_file" accept=".cer" required style="width:100%;font-size:.8rem;">
                    </div>
                    <div>
                        <label style="font-size:.75rem;color:#374151;display:block;margin-bottom:.25rem;">Llave .key</label>
                        <input type="file" name="key_file" accept=".key" required style="width:100%;font-size:.8rem;">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:.75rem;color:#374151;display:block;margin-bottom:.25rem;">Contraseña</label>
                        <input type="password" name="key_password" required autocomplete="off"
                               style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.8rem;box-sizing:border-box;">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;cursor:pointer;">
                    <input type="checkbox" name="guardar_credencial" value="1" style="accent-color:#7c3aed;">
                    Guardar e.firma para no pedirla la próxima vez
                </label>
            </div>
            @else
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
                ✓ Se usará la e.firma guardada de <strong>{{ $credencial->rfc }}</strong> automáticamente.
            </div>
            @endif

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" style="background:#7c3aed;color:#fff;border:none;padding:.6rem 1.75rem;border-radius:.5rem;cursor:pointer;font-size:.875rem;font-weight:600;">
                    Solicitar al SAT →
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HISTORIAL                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <h2 style="font-size:1rem;font-weight:700;color:#374151;margin-bottom:.75rem;">Solicitudes anteriores</h2>

    @if($solicitudes->isEmpty())
        <p style="color:#9ca3af;font-size:.875rem;text-align:center;padding:2rem;">No hay solicitudes aún.</p>
    @else
    <div style="display:flex;flex-direction:column;gap:1rem;">
        @foreach($solicitudes as $sol)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;"
             @if(in_array($sol->estado, ['en_proceso','pendiente'])) data-polling="true" @endif>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem;">
                <div>
                    <span style="background:{{ $sol->colorEstado() }}22;color:{{ $sol->colorEstado() }};padding:.25rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:700;">
                        {{ $sol->etiquetaEstado() }}
                    </span>
                    <span style="margin-left:.75rem;font-size:.8rem;font-weight:600;color:{{ $sol->tipo_factura === 'emitida' ? '#16a34a' : '#dc2626' }};">
                        {{ $sol->tipo_factura === 'emitida' ? 'Emitidas' : 'Recibidas' }}
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <span style="font-size:.75rem;color:#9ca3af;">{{ $sol->created_at->format('d/m/Y H:i') }}</span>
                    <form method="POST" action="{{ route('sat.solicitud.eliminar', $sol) }}"
                          onsubmit="return confirm('¿Eliminar esta solicitud del historial?')" style="margin:0;">
                        @csrf @method('DELETE')
                        <button type="submit" title="Eliminar del historial"
                                style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:.85rem;padding:.1rem .3rem;border-radius:.25rem;line-height:1;"
                                onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#d1d5db'">✕</button>
                    </form>
                </div>
            </div>

            <div style="display:flex;gap:1.5rem;font-size:.8rem;color:#6b7280;margin-bottom:.75rem;flex-wrap:wrap;">
                <span>📅 {{ $sol->fecha_inicio->format('d/m/Y') }} — {{ $sol->fecha_fin->format('d/m/Y') }}</span>
                @if($sol->total_cfdis > 0)<span>{{ number_format($sol->total_cfdis) }} CFDIs</span>@endif
                @if($sol->facturas_importadas > 0)<span style="color:#16a34a;">✓ {{ $sol->facturas_importadas }} importadas</span>@endif
                @if($sol->facturas_duplicadas > 0)<span>{{ $sol->facturas_duplicadas }} duplicadas omitidas</span>@endif
            </div>

            @if($sol->mensaje_error)
                <p style="font-size:.75rem;color:#dc2626;margin-bottom:.75rem;word-break:break-all;">{{ Str::limit($sol->mensaje_error, 150) }}</p>
            @endif

            {{-- Acciones --}}
            @if($sol->estado === 'descargada')
                <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
                    @if($sol->facturas_importadas > 0)
                        <a href="{{ route('facturas.index', ['año' => $sol->fecha_inicio->year]) }}"
                           style="background:#16a34a;color:#fff;padding:.4rem 1rem;border-radius:.375rem;text-decoration:none;font-size:.8rem;font-weight:700;">
                            Ver {{ $sol->facturas_importadas }} facturas en Contabilidad →
                        </a>
                    @endif

                    {{-- Botón re-importar siempre visible --}}
                    <form method="POST" action="{{ route('sat.reimportar', $sol) }}" enctype="multipart/form-data" style="margin:0;">
                        @csrf
                        @if(!$credencial)
                        <details style="border:1px solid #e5e7eb;border-radius:.5rem;padding:.6rem;margin-bottom:.4rem;">
                            <summary style="font-size:.75rem;color:#374151;cursor:pointer;">Subir e.firma para re-importar</summary>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.4rem;margin-top:.4rem;">
                                <input type="file" name="cer_file" accept=".cer" required style="font-size:.7rem;">
                                <input type="file" name="key_file" accept=".key" required style="font-size:.7rem;">
                                <div style="grid-column:1/-1;">
                                    <input type="password" name="key_password" required autocomplete="off" placeholder="Contraseña"
                                           style="width:100%;border:1px solid #d1d5db;border-radius:.25rem;padding:.3rem .5rem;font-size:.7rem;box-sizing:border-box;">
                                </div>
                            </div>
                        </details>
                        @endif
                        <button type="submit"
                                style="background:#f3f4f6;border:1px solid #d1d5db;color:#374151;padding:.4rem .9rem;border-radius:.375rem;cursor:pointer;font-size:.75rem;"
                                onclick="return confirm('¿Re-importar los XMLs? Las facturas nuevas se agregarán, las existentes se omiten.')">
                            ↺ Re-importar XMLs
                        </button>
                    </form>

                    @if($sol->facturas_importadas == 0 && $sol->facturas_duplicadas == 0)
                        <span style="font-size:.75rem;color:#dc2626;font-weight:600;">
                            ⚠ No se importó nada — usa Re-importar para intentar de nuevo
                        </span>
                    @else
                        <span style="font-size:.75rem;color:#9ca3af;">
                            {{ $sol->facturas_duplicadas }} ya existían (omitidas)
                        </span>
                    @endif
                </div>

            @elseif(in_array($sol->estado, ['en_proceso','pendiente']))
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.5rem;padding:.6rem .75rem;margin-bottom:.6rem;font-size:.75rem;color:#1e40af;">
                    ⏳ El SAT normalmente tarda <strong>5–30 minutos</strong> en preparar los CFDIs.
                    @if($credencial)
                        <span id="countdown-{{ $sol->id }}" style="margin-left:.5rem;color:#6b7280;"></span>
                    @endif
                </div>
                <form method="POST" action="{{ route('sat.verificar', $sol) }}" enctype="multipart/form-data">
                    @csrf
                    @if(!$credencial)
                    <details style="border:1px solid #e5e7eb;border-radius:.5rem;padding:.75rem;margin-bottom:.5rem;">
                        <summary style="font-size:.8rem;font-weight:600;color:#2563eb;cursor:pointer;">Subir e.firma para verificar</summary>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;margin-top:.5rem;">
                            <input type="file" name="cer_file" accept=".cer" required style="font-size:.75rem;">
                            <input type="file" name="key_file" accept=".key" required style="font-size:.75rem;">
                            <div style="grid-column:1/-1;">
                                <input type="password" name="key_password" required autocomplete="off" placeholder="Contraseña"
                                       style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.75rem;box-sizing:border-box;">
                            </div>
                        </div>
                    </details>
                    @else
                    <p style="font-size:.75rem;color:#16a34a;margin-bottom:.5rem;">✓ Se usará la e.firma guardada — verificación automática activa</p>
                    @endif
                    <button type="submit" style="background:#2563eb;color:#fff;border:none;padding:.4rem 1rem;border-radius:.375rem;cursor:pointer;font-size:.75rem;">
                        Verificar ahora
                    </button>
                </form>

            @elseif($sol->estado === 'lista')
                <form method="POST" action="{{ route('sat.descargar', $sol) }}" enctype="multipart/form-data">
                    @csrf
                    @if(!$credencial)
                    <details style="border:2px solid #bbf7d0;border-radius:.5rem;padding:.75rem;background:#f0fdf4;margin-bottom:.5rem;">
                        <summary style="font-size:.8rem;font-weight:700;color:#16a34a;cursor:pointer;">Subir e.firma para descargar</summary>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;margin-top:.5rem;">
                            <input type="file" name="cer_file" accept=".cer" required style="font-size:.75rem;">
                            <input type="file" name="key_file" accept=".key" required style="font-size:.75rem;">
                            <div style="grid-column:1/-1;">
                                <input type="password" name="key_password" required autocomplete="off" placeholder="Contraseña"
                                       style="width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.75rem;box-sizing:border-box;">
                            </div>
                        </div>
                    </details>
                    @else
                    <p style="font-size:.75rem;color:#16a34a;margin-bottom:.5rem;">✓ Se usará la e.firma guardada — {{ $sol->total_cfdis }} CFDIs listos</p>
                    @endif
                    <button type="submit" style="background:#16a34a;color:#fff;border:none;padding:.4rem 1.25rem;border-radius:.375rem;cursor:pointer;font-size:.8rem;font-weight:700;">
                        Descargar e importar →
                    </button>
                </form>
            @endif
        </div>
        @endforeach
    </div>
    @if($solicitudes->hasPages())
    <div style="display:flex;justify-content:center;align-items:center;gap:.4rem;margin-top:1rem;font-size:.8rem;">
        @if($solicitudes->onFirstPage())
            <span style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #e5e7eb;color:#d1d5db;">‹</span>
        @else
            <a href="{{ $solicitudes->previousPageUrl() }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">‹</a>
        @endif
        @foreach($solicitudes->getUrlRange(max(1,$solicitudes->currentPage()-2), min($solicitudes->lastPage(),$solicitudes->currentPage()+2)) as $page => $url)
            @if($page == $solicitudes->currentPage())
                <span style="padding:.3rem .6rem;border-radius:.375rem;background:#7c3aed;color:#fff;font-weight:600;">{{ $page }}</span>
            @else
                <a href="{{ $url }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">{{ $page }}</a>
            @endif
        @endforeach
        @if($solicitudes->hasMorePages())
            <a href="{{ $solicitudes->nextPageUrl() }}" style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #d1d5db;color:#374151;text-decoration:none;">›</a>
        @else
            <span style="padding:.3rem .6rem;border-radius:.375rem;border:1px solid #e5e7eb;color:#d1d5db;">›</span>
        @endif
        <span style="color:#9ca3af;margin-left:.25rem;">Página {{ $solicitudes->currentPage() }} de {{ $solicitudes->lastPage() }}</span>
    </div>
    @endif
    @endif

</div>

@push('scripts')
<script>
(function() {
    // ── Selector periodo ──────────────────────────────────────────────
    const radios     = document.querySelectorAll('input[name="modo_fecha"]');
    const panelAño   = document.getElementById('panel-año');
    const panelRango = document.getElementById('panel-rango');

    function toggle() {
        const val = document.querySelector('input[name="modo_fecha"]:checked')?.value;
        panelAño.style.display   = val === 'año'   ? 'block' : 'none';
        panelRango.style.display = val === 'rango' ? 'block' : 'none';
    }
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();

    // ── Auto-polling cuando hay solicitudes en proceso ────────────────
    const hayEnProceso = document.querySelectorAll('[data-polling="true"]').length > 0;
    if (!hayEnProceso) return;

    const INTERVALO = 30; // segundos entre recarga
    let segundos = INTERVALO;

    // Actualizar counters en todas las tarjetas en proceso
    function actualizarCountdowns() {
        document.querySelectorAll('[id^="countdown-"]').forEach(el => {
            el.textContent = segundos > 0
                ? `· Verificando automáticamente en ${segundos}s`
                : '· Verificando…';
        });
    }

    actualizarCountdowns();

    const timer = setInterval(() => {
        segundos--;
        actualizarCountdowns();
        if (segundos <= 0) {
            clearInterval(timer);
            window.location.reload();
        }
    }, 1000);
})();
</script>
@endpush
@endsection
