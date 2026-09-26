<?php

namespace App\Http\Controllers;

use App\Models\Declaracion;
use App\Models\Factura;
use App\Services\FacturaSATParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::where('user_id', Auth::id());

        if ($request->filled('año')) {
            $query->where('año', $request->año);
        }
        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo_factura', $request->tipo);
        }
        if ($request->filled('archivos')) {
            match ($request->archivos) {
                'con_xml' => $query->whereNotNull('xml_path'),
                'con_pdf' => $query->whereNotNull('pdf_path'),
                'sin_pdf' => $query->whereNull('pdf_path'),
                default => null,
            };
        }

        $facturas = $query->orderByDesc('fecha_emision')->paginate(20)->withQueryString();

        $años = Factura::where('user_id', Auth::id())
            ->selectRaw('DISTINCT año')
            ->orderByDesc('año')
            ->pluck('año');

        $resumen = $this->resumenGlobal(Auth::id());

        return view('facturas.index', compact('facturas', 'años', 'resumen'));
    }

    public function create()
    {
        return view('facturas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_factura' => 'required|in:emitida,recibida',
            'xml_file' => 'required|file|mimes:xml|max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'es_deducible' => 'nullable|boolean',
            'notas' => 'nullable|string|max:500',
        ]);

        $xmlContent = file_get_contents($request->file('xml_file')->getRealPath());

        $parser = new FacturaSATParser;
        try {
            $datos = $parser->parse($xmlContent, $request->file('xml_file')->getClientOriginalName());
        } catch (\Exception $e) {
            return back()->withErrors(['xml_file' => 'Error al leer el XML: '.$e->getMessage()])->withInput();
        }

        if (Factura::where('uuid', $datos['uuid'])->exists()) {
            return back()->withErrors(['xml_file' => 'Esta factura ya fue registrada (UUID duplicado).'])->withInput();
        }

        // Guardar archivos
        $xmlPath = $request->file('xml_file')->store('facturas/xml', 'local');
        $pdfPath = $request->hasFile('pdf_file')
            ? $request->file('pdf_file')->store('facturas/pdf', 'local')
            : null;

        Factura::create(array_merge($datos, [
            'tipo_factura' => $request->tipo_factura,
            'es_deducible' => $request->tipo_factura === 'recibida'
                ? ($request->boolean('es_deducible', true))
                : true,
            'notas' => $request->notas,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'user_id' => Auth::id(),
        ]));

        return redirect()->route('facturas.index')
            ->with('success', 'Factura registrada correctamente.');
    }

    public function show(Factura $factura)
    {
        $this->checkOwner($factura);

        return view('facturas.show', compact('factura'));
    }

    public function edit(Factura $factura)
    {
        $this->checkOwner($factura);

        return view('facturas.edit', compact('factura'));
    }

    public function update(Request $request, Factura $factura)
    {
        $this->checkOwner($factura);

        $request->validate([
            'es_deducible' => 'nullable|boolean',
            'notas' => 'nullable|string|max:500',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $data = [
            'es_deducible' => $request->boolean('es_deducible', true),
            'notas' => $request->notas,
        ];

        if ($request->hasFile('pdf_file')) {
            if ($factura->pdf_path) {
                Storage::disk('local')->delete($factura->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf_file')->store('facturas/pdf', 'local');
        }

        $factura->update($data);

        return redirect()->route('facturas.show', $factura)
            ->with('success', 'Factura actualizada.');
    }

    public function destroy(Factura $factura)
    {
        $this->authorize($factura);

        if ($factura->xml_path) {
            Storage::disk('local')->delete($factura->xml_path);
        }
        if ($factura->pdf_path) {
            Storage::disk('local')->delete($factura->pdf_path);
        }

        $factura->delete();

        return redirect()->route('facturas.index')
            ->with('success', 'Factura eliminada.');
    }

    public function guardarDeclaracion(Request $request)
    {
        $request->validate([
            'año' => 'required|integer|min:2010|max:'.(now()->year + 1),
            'mes' => 'nullable|integer|min:1|max:12',
            'iva_pagado' => 'nullable|numeric|min:0',
            'isr_pagado' => 'nullable|numeric|min:0',
            'fecha_presentacion' => 'nullable|date',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:300',
        ]);

        Declaracion::updateOrCreate(
            ['user_id' => Auth::id(), 'año' => $request->año, 'mes' => $request->mes ?: null],
            [
                'iva_pagado' => $request->iva_pagado ?? 0,
                'isr_pagado' => $request->isr_pagado ?? 0,
                'fecha_presentacion' => $request->fecha_presentacion ?: null,
                'fecha_pago' => $request->fecha_pago ?: null,
                'notas' => $request->notas,
            ]
        );

        $redirect = $request->mes
            ? route('facturas.reporte-mensual', ['año' => $request->año, 'mes' => $request->mes])
            : route('facturas.reporte-anual', ['año' => $request->año]);

        return redirect($redirect)->with('success', 'Estado de declaración guardado.');
    }

    public function reporteMensual(Request $request)
    {
        $año = $request->get('año', now()->year);
        $mes = $request->get('mes', now()->month);

        $emitidas = Factura::where('user_id', Auth::id())
            ->where('tipo_factura', 'emitida')
            ->where('año', $año)
            ->where('mes', $mes)
            ->get();

        $recibidas = Factura::where('user_id', Auth::id())
            ->where('tipo_factura', 'recibida')
            ->where('es_deducible', true)
            ->where('año', $año)
            ->where('mes', $mes)
            ->get();

        // Ingresos
        $totalIngresos = $emitidas->sum('subtotal');
        $ivaCobrado = $emitidas->sum('iva_trasladado');
        $ivaRetenidoClientes = $emitidas->sum('iva_retenido');
        $isrRetenidoClientes = $emitidas->sum('isr_retenido');

        // Egresos deducibles
        $totalEgresos = $recibidas->sum('subtotal');
        $ivaAcreditable = $recibidas->sum('iva_trasladado');

        // Cálculos fiscales
        $tasaResico = Factura::tasaResicoMensual($totalIngresos);
        $isrResico = round($totalIngresos * $tasaResico, 2);
        $isrAPagar = max(0, $isrResico - $isrRetenidoClientes);

        $ivaAPagar = round($ivaCobrado - $ivaRetenidoClientes - $ivaAcreditable, 2);

        $años = Factura::where('user_id', Auth::id())
            ->selectRaw('DISTINCT año')->orderByDesc('año')->pluck('año');

        $declaracion = Declaracion::where('user_id', Auth::id())
            ->where('año', $año)->where('mes', $mes)->first();

        return view('facturas.reporte-mensual', compact(
            'año', 'mes', 'años',
            'emitidas', 'recibidas',
            'totalIngresos', 'ivaCobrado', 'ivaRetenidoClientes', 'isrRetenidoClientes',
            'totalEgresos', 'ivaAcreditable',
            'tasaResico', 'isrResico', 'isrAPagar', 'ivaAPagar',
            'declaracion'
        ));
    }

    public function reporteAnual(Request $request)
    {
        $año = $request->get('año', now()->year);

        $meses = [];
        $totalAnualIngresos = 0;
        $totalAnualIvaCobrado = 0;
        $totalAnualIvaRet = 0;
        $totalAnualIsrRet = 0;
        $totalAnualEgresos = 0;
        $totalAnualIvaAcred = 0;
        $totalAnualIsrResico = 0;
        $totalAnualIvaPagar = 0;
        $totalAnualIsrPagar = 0;

        for ($m = 1; $m <= 12; $m++) {
            $emitidas = Factura::where('user_id', Auth::id())
                ->where('tipo_factura', 'emitida')->where('año', $año)->where('mes', $m)->get();
            $recibidas = Factura::where('user_id', Auth::id())
                ->where('tipo_factura', 'recibida')->where('es_deducible', true)
                ->where('año', $año)->where('mes', $m)->get();

            $ingresos = $emitidas->sum('subtotal');
            $ivaCobrado = $emitidas->sum('iva_trasladado');
            $ivaRet = $emitidas->sum('iva_retenido');
            $isrRet = $emitidas->sum('isr_retenido');
            $egresos = $recibidas->sum('subtotal');
            $ivaAcred = $recibidas->sum('iva_trasladado');
            $isrResico = round($ingresos * Factura::tasaResicoMensual($ingresos), 2);
            $ivaPagar = round($ivaCobrado - $ivaRet - $ivaAcred, 2);
            $isrPagar = max(0, $isrResico - $isrRet);

            $meses[$m] = compact(
                'ingresos', 'ivaCobrado', 'ivaRet', 'isrRet',
                'egresos', 'ivaAcred', 'isrResico', 'ivaPagar', 'isrPagar'
            );

            $totalAnualIngresos += $ingresos;
            $totalAnualIvaCobrado += $ivaCobrado;
            $totalAnualIvaRet += $ivaRet;
            $totalAnualIsrRet += $isrRet;
            $totalAnualEgresos += $egresos;
            $totalAnualIvaAcred += $ivaAcred;
            $totalAnualIsrResico += $isrResico;
            $totalAnualIvaPagar += $ivaPagar;
            $totalAnualIsrPagar += $isrPagar;
        }

        $años = Factura::where('user_id', Auth::id())
            ->selectRaw('DISTINCT año')->orderByDesc('año')->pluck('año');

        $declaraciones = Declaracion::where('user_id', Auth::id())
            ->where('año', $año)
            ->get()
            ->keyBy(fn ($d) => $d->mes ?? 0); // 0 = anual

        return view('facturas.reporte-anual', compact(
            'año', 'años', 'meses',
            'totalAnualIngresos', 'totalAnualIvaCobrado', 'totalAnualIvaRet', 'totalAnualIsrRet',
            'totalAnualEgresos', 'totalAnualIvaAcred', 'totalAnualIsrResico',
            'totalAnualIvaPagar', 'totalAnualIsrPagar',
            'declaraciones'
        ));
    }

    public function descargar(Factura $factura, string $tipo)
    {
        $this->checkOwner($factura);

        $path = $tipo === 'pdf' ? $factura->pdf_path : $factura->xml_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return response()->download(Storage::disk('local')->path($path));
    }

    public function visualizar(Factura $factura, string $tipo)
    {
        $this->checkOwner($factura);

        $path = $tipo === 'pdf' ? $factura->pdf_path : $factura->xml_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $mime = $tipo === 'pdf' ? 'application/pdf' : 'text/xml';

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private function resumenGlobal(int $userId): array
    {
        $uid = $userId;

        // Emitidas (ingresos)
        $emBase = Factura::where('user_id', $uid)->where('tipo_factura', 'emitida');
        $totalIngresos = (float) (clone $emBase)->sum('subtotal');
        $ivaCobrado = (float) (clone $emBase)->sum('iva_trasladado');
        $ivaRetenidoClientes = (float) (clone $emBase)->sum('iva_retenido');
        $isrRetenidoClientes = (float) (clone $emBase)->sum('isr_retenido');

        // Recibidas deducibles (egresos)
        $reBase = Factura::where('user_id', $uid)->where('tipo_factura', 'recibida')->where('es_deducible', true);
        $totalEgresos = (float) (clone $reBase)->sum('subtotal');
        $ivaAcreditable = (float) (clone $reBase)->sum('iva_trasladado');

        // ISR RESICO: calculado mes a mes (la tasa varía según ingreso mensual)
        $meses = Factura::where('user_id', $uid)
            ->where('tipo_factura', 'emitida')
            ->selectRaw('año, mes, SUM(subtotal) as ingresos_mes')
            ->groupBy('año', 'mes')
            ->get();

        $isrResicoTotal = 0.0;
        foreach ($meses as $m) {
            $isrResicoTotal += $m->ingresos_mes * Factura::tasaResicoMensual((float) $m->ingresos_mes);
        }
        $isrResicoTotal = round($isrResicoTotal, 2);

        $ivaAPagar = round($ivaCobrado - $ivaRetenidoClientes - $ivaAcreditable, 2);
        $isrAPagar = max(0.0, round($isrResicoTotal - $isrRetenidoClientes, 2));

        return compact(
            'totalIngresos', 'totalEgresos',
            'ivaCobrado', 'ivaRetenidoClientes', 'ivaAcreditable', 'ivaAPagar',
            'isrRetenidoClientes', 'isrResicoTotal', 'isrAPagar'
        );
    }

    private function checkOwner(Factura $factura): void
    {
        if ($factura->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
