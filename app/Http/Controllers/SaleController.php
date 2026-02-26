<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['client', 'user'])
            ->completed()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        // Ensure user belongs to same organization
        if ($sale->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $sale->load(['items.variant.product', 'client', 'user', 'organization']);

        return view('sales.show', compact('sale'));
    }

    public function downloadPdf(Sale $sale)
    {
        if ($sale->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $sale->load(['items.variant.product', 'client', 'user', 'organization']);

        // QR Code content
        // You might want a URL to verify the sale or just the folio
        $qrContent = route('sales.show', $sale->id);
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate($qrContent));

        $pdf = Pdf::loadView('sales.pdf', compact('sale', 'qrCode'));

        return $pdf->download("Ticket-{$sale->folio}.pdf");
    }
}
