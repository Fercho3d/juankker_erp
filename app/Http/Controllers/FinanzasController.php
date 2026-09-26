<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resumen de ingresos, gastos y ganancia a partir de las facturas del SAT:
 * por mes, gastos por tipo (según el uso del CFDI) y principales clientes y proveedores.
 */
class FinanzasController extends Controller
{
    /** Tipo de gasto según el uso del CFDI que pediste al proveedor. */
    public const TIPOS = [
        'negocio' => 'Gastos del negocio (deducibles)',
        'personal' => 'Deducciones personales (anual)',
        'sin_efectos' => 'Sin efectos fiscales (no deducibles)',
    ];

    public function index(Request $request)
    {
        // Complementos de pago no suman; notas de crédito restan
        $base = fn () => DB::table('facturas')
            ->where('user_id', Auth::id())
            ->where('tipo_comprobante', '!=', 'P');
        $importe = "CASE WHEN tipo_comprobante = 'E' THEN -1 ELSE 1 END * (subtotal - descuento)";

        $años = $base()->distinct()->orderByDesc('año')->pluck('año');
        $año = (int) $request->input('año', $años->first() ?? now()->year);

        $porMes = $base()->where('año', $año)
            ->selectRaw("mes,
                SUM(CASE WHEN tipo_factura = 'emitida' THEN $importe ELSE 0 END) ingresos,
                SUM(CASE WHEN tipo_factura = 'recibida' THEN $importe ELSE 0 END) gastos,
                SUM(CASE WHEN tipo_factura = 'recibida' AND es_deducible THEN $importe ELSE 0 END) deducibles")
            ->groupBy('mes')->get()->keyBy('mes');
        $meses = collect(range(1, 12))->map(fn ($m) => [
            'mes' => $m,
            'ingresos' => round((float) ($porMes[$m]->ingresos ?? 0)),
            'gastos' => round((float) ($porMes[$m]->gastos ?? 0)),
            'deducibles' => round((float) ($porMes[$m]->deducibles ?? 0)),
        ])->map(fn ($m) => $m + ['ganancia' => $m['ingresos'] - $m['gastos']]);

        $recibidas = $base()->where('año', $año)->where('tipo_factura', 'recibida');
        $porTipo = (clone $recibidas)->selectRaw("uso_cfdi, SUM($importe) total, COUNT(*) n")->groupBy('uso_cfdi')->get()
            ->groupBy(fn ($r) => $this->tipo($r->uso_cfdi))
            ->map(fn ($g) => ['total' => round($g->sum('total')), 'n' => $g->sum('n')]);

        $proveedores = (clone $recibidas)
            ->selectRaw("nombre_emisor nombre, rfc_emisor rfc, SUM($importe) total, COUNT(*) n, GROUP_CONCAT(DISTINCT uso_cfdi) usos")
            ->groupBy('nombre_emisor', 'rfc_emisor')->orderByDesc('total')->limit(10)->get();
        $clientes = $base()->where('año', $año)->where('tipo_factura', 'emitida')
            ->selectRaw("nombre_receptor nombre, rfc_receptor rfc, SUM($importe) total, COUNT(*) n")
            ->groupBy('nombre_receptor', 'rfc_receptor')->orderByDesc('total')->limit(10)->get();

        return view('finanzas.index', compact('años', 'año', 'meses', 'porTipo', 'proveedores', 'clientes'));
    }

    public function tipo(?string $usoCfdi): string
    {
        return match (true) {
            $usoCfdi === 'S01' => 'sin_efectos',
            str_starts_with((string) $usoCfdi, 'D') => 'personal',
            default => 'negocio',
        };
    }

    /** Nombre corto del uso del CFDI, para explicar cada proveedor. */
    public static function nombreUso(string $uso): string
    {
        return [
            'G01' => 'Mercancías', 'G02' => 'Devoluciones', 'G03' => 'Gastos en general', 'P01' => 'Por definir',
            'S01' => 'Sin efectos fiscales', 'CP01' => 'Pagos', 'D01' => 'Médicos y dentales', 'D02' => 'Gastos médicos por incapacidad',
            'D03' => 'Funerales', 'D04' => 'Donativos', 'D07' => 'Seguros médicos', 'D10' => 'Colegiaturas',
        ][$uso] ?? (str_starts_with($uso, 'I') ? 'Inversión' : $uso);
    }
}
