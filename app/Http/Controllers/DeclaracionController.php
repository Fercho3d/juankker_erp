<?php

namespace App\Http\Controllers;

use App\Models\Declaracion;
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
        $totales = $this->totalesPorMes();
        $declaraciones = Declaracion::where('user_id', Auth::id())->get()
            ->keyBy(fn ($d) => $d->año.'-'.($d->mes ?? 0));

        $primerAño = min($totales->keys()->map(fn ($k) => (int) $k)->min() ?? now()->year,
            $declaraciones->min('año') ?? now()->year);
        $ultimoMes = now()->subMonth();

        $periodos = [];
        for ($año = $primerAño; $año <= $ultimoMes->year; $año++) {
            $mesFinal = $año === $ultimoMes->year ? $ultimoMes->month : 12;
            $anual = ['ingresos' => 0, 'gastos' => 0, 'iva' => 0];
            for ($mes = 1; $mes <= $mesFinal; $mes++) {
                $t = $totales->get("$año-$mes", ['ingresos' => 0, 'gastos' => 0, 'iva' => 0]);
                $periodos[] = $this->periodo($año, $mes, $t, $declaraciones->get("$año-$mes"));
                foreach ($anual as $k => $v) {
                    $anual[$k] = $v + $t[$k];
                }
            }
            if ($año < now()->year) {
                $periodos[] = $this->periodo($año, null, $anual, $declaraciones->get("$año-0"));
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

    /** Ingresos, gastos deducibles e IVA neto por "año-mes"; las notas de crédito (E) restan. */
    private function totalesPorMes()
    {
        return DB::table('facturas')
            ->where('user_id', Auth::id())
            ->where('tipo_comprobante', '!=', 'P')
            ->where(fn ($q) => $q->where('tipo_factura', 'emitida')->orWhere('es_deducible', true))
            ->selectRaw("año, mes,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * (subtotal - descuento) ELSE 0 END) ingresos,
                SUM(CASE WHEN tipo_factura = 'recibida' THEN s * (subtotal - descuento) ELSE 0 END) gastos,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN s * (iva_trasladado - iva_retenido) ELSE -s * iva_trasladado END) iva")
            ->fromSub(DB::table('facturas')->selectRaw("*, CASE WHEN tipo_comprobante = 'E' THEN -1 ELSE 1 END s"), 'facturas')
            ->groupBy('año', 'mes')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->año}-{$r->mes}" => [
                'ingresos' => (float) $r->ingresos, 'gastos' => (float) $r->gastos, 'iva' => (float) $r->iva,
            ]]);
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
