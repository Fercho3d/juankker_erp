<?php

namespace App\Http\Controllers;

use App\Models\Declaracion;
use App\Services\DeclaracionSat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tablero de declaraciones: todos los meses y anuales en una tabla, con su
 * estado ante el SAT y el acuse y comprobante de pago de cada uno.
 */
class DeclaracionController extends Controller
{
    public function index(Request $request)
    {
        [$hojas, $declaraciones] = $this->hojas();

        $periodos = [];
        foreach ($hojas as $clave => $h) {
            $periodos[] = $this->periodo($h['año'], $h['mes'], $h, $declaraciones->get($clave));
            if ($h['mes'] === 12) {
                $delAño = array_filter($hojas, fn ($x) => $x['año'] === $h['año']);
                $anual = array_map(fn ($k) => array_sum(array_column($delAño, $k)), array_flip(array_keys($h)));
                $periodos[] = $this->periodo($h['año'], null, $anual, $declaraciones->get($h['año'].'-0'));
            }
        }

        $siguiente = collect($periodos)->first(fn ($p) => $p['estado'] !== 'presentada');
        $conteo = collect($periodos)->countBy('estado');
        $años = collect($periodos)->pluck('año')->unique()->sortDesc()->values();

        if ($request->filled('año')) {
            $periodos = array_filter($periodos, fn ($p) => $p['año'] === (int) $request->año);
        }
        if ($request->estado === 'pendientes') {
            $periodos = array_filter($periodos, fn ($p) => $p['estado'] !== 'presentada');
        }

        return view('declaraciones.index', compact('periodos', 'siguiente', 'conteo', 'años'));
    }

    /** Los campos del formulario del SAT de un mes, en el orden en que los pide. */
    public function hoja(Request $request)
    {
        [$hojas, $declaraciones] = $this->hojas();
        $clave = (int) $request->año.'-'.(int) $request->mes;
        abort_unless(isset($hojas[$clave]), 404);

        $claves = array_keys($hojas);
        $i = array_search($clave, $claves, true);

        return view('declaraciones.hoja', [
            'h' => $hojas[$clave],
            'declaracion' => $declaraciones->get($clave),
            'anterior' => $hojas[$claves[$i - 1] ?? ''] ?? null,
            'siguiente' => $hojas[$claves[$i + 1] ?? ''] ?? null,
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'año' => 'required|integer|min:2010|max:'.now()->year,
            'mes' => 'nullable|integer|min:1|max:12',
            'fecha_presentacion' => 'nullable|date',
            'fecha_pago' => 'nullable|date',
            'iva_pagado' => 'nullable|numeric|min:0',
            'isr_pagado' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:300',
            'acuse' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'pago' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $declaracion = Declaracion::firstOrNew([
            'user_id' => Auth::id(), 'año' => $request->año, 'mes' => $request->mes ?: null,
        ]);
        $declaracion->fill($request->only('fecha_presentacion', 'fecha_pago', 'notas'));
        $declaracion->iva_pagado = $request->iva_pagado ?? $declaracion->iva_pagado ?? 0;
        $declaracion->isr_pagado = $request->isr_pagado ?? $declaracion->isr_pagado ?? 0;

        foreach (['acuse', 'pago'] as $archivo) {
            if ($request->hasFile($archivo)) {
                Storage::disk('local')->delete((string) $declaracion->{$archivo.'_path'});
                $declaracion->{$archivo.'_path'} = $request->file($archivo)->storeAs(
                    'declaraciones/'.Auth::id(),
                    $request->año.'-'.($request->mes ? str_pad($request->mes, 2, '0', STR_PAD_LEFT) : 'anual')
                        ."-{$archivo}.".$request->file($archivo)->extension(),
                    'local'
                );
            }
        }
        // Subir el acuse es presentarla; subir el comprobante, pagarla
        $declaracion->fecha_presentacion ??= $declaracion->acuse_path ? now() : null;
        $declaracion->fecha_pago ??= $declaracion->pago_path ? now() : null;
        $declaracion->save();

        return redirect()->to(route('declaraciones.index').'?'.$request->query('volver', '').'#p-'.$request->año.'-'.($request->mes ?: 0))
            ->with('success', 'Declaración guardada.');
    }

    public function archivo(Declaracion $declaracion, string $archivo)
    {
        abort_unless($declaracion->user_id === Auth::id(), 403);
        $path = $declaracion->{$archivo.'_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Hojas SAT de todos los meses hasta el mes pasado, y las declaraciones por "año-mes" (0 = anual). */
    private function hojas(): array
    {
        $totales = DB::table('facturas')
            ->where('user_id', Auth::id())
            ->where('tipo_comprobante', '!=', 'P')
            ->where(fn ($q) => $q->where('tipo_factura', 'emitida')->orWhere('es_deducible', true))
            ->selectRaw("año, mes,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * (subtotal - descuento) ELSE 0 END) ingresos,
                SUM(CASE WHEN tipo_factura = 'recibida' THEN s * (subtotal - descuento) ELSE 0 END) gastos,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * iva_trasladado ELSE 0 END) iva_trasladado,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * iva_retenido ELSE 0 END) iva_retenido,
                SUM(CASE WHEN tipo_factura = 'recibida' THEN s * iva_trasladado ELSE 0 END) iva_acreditable,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * isr_retenido ELSE 0 END) isr_retenido")
            ->fromSub(DB::table('facturas')->selectRaw("*, CASE WHEN tipo_comprobante = 'E' THEN -1 ELSE 1 END s"), 'facturas')
            ->groupBy('año', 'mes')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->año}-{$r->mes}" => array_map('floatval', array_diff_key((array) $r, ['año' => 0, 'mes' => 0]))]);

        $declaraciones = Declaracion::where('user_id', Auth::id())->get()
            ->keyBy(fn ($d) => $d->año.'-'.($d->mes ?? 0));
        // Lo ya presentado manda sobre lo calculado para los pagos provisionales siguientes
        $declarado = $declaraciones->filter(fn ($d) => $d->isPresentada())
            ->map(fn ($d) => ['isr' => (float) $d->isr_pagado, 'iva' => (float) $d->iva_pagado])->all();

        $primerAño = min($totales->keys()->map(fn ($k) => (int) $k)->min() ?? now()->year, $declaraciones->min('año') ?? now()->year);
        $hasta = now()->subMonth();

        $hojas = DeclaracionSat::hojas($totales->all(), $declarado, $primerAño, $hasta->year, $hasta->month);
        // Estimado de lo que el SAT sumará por inflación a lo que falta presentar; se trunca como en el portal
        foreach ($hojas as $clave => &$h) {
            $factor = isset($declarado[$clave]) ? 1.0 : DeclaracionSat::factorActualizacion($h['año'], $h['mes'], config('inpc'));
            $h['actualizacion'] = floor($h['isr_cargo'] * ($factor - 1)) + floor($h['iva_cargo'] * ($factor - 1));
        }
        unset($h);

        return [$hojas, $declaraciones];
    }

    private function periodo(int $año, ?int $mes, array $totales, ?Declaracion $declaracion): array
    {
        return $totales + [
            'año' => $año,
            'mes' => $mes,
            'declaracion' => $declaracion,
            'estado' => match (true) {
                (bool) $declaracion?->isPresentada() => 'presentada',
                (bool) $declaracion?->omitida_sat => 'omitida',
                default => 'por_presentar',
            },
        ];
    }
}
