<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Client;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return view('pos.index', compact('categories'));
    }

    // API: Search products
    public function search(Request $request)
    {
        $term = $request->query('q');

        $variants = ProductVariant::search($term)
            ->where('activo', true)
            ->with(['product', 'attributeValues'])
            ->limit(20)
            ->get();

        $results = $variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->getVariantName(),
                'sku' => $variant->sku,
                'price' => $variant->precio_venta,
                'stock' => $variant->stock_actual,
                'image' => $variant->imagen ?? $variant->product->imagen,
            ];
        });

        return response()->json($results);
    }

    // API: Get current cart (pending sale)
    public function getCart()
    {
        $sale = $this->getCurrentSale();
        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Add item to cart
    public function addToCart(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $sale = $this->getCurrentSale();
        $variant = ProductVariant::find($request->variant_id);

        // Check stock
        if ($variant->stock_actual < $request->quantity) {
            return response()->json(['error' => 'Stock insuficiente'], 422);
        }

        // Check if item exists in cart
        $item = $sale->items()->where('product_variant_id', $variant->id)->first();

        if ($item) {
            $item->cantidad += $request->quantity;
            $item->importe = $item->cantidad * $item->precio_unitario;
            $item->save();
        } else {
            $sale->items()->create([
                'product_variant_id' => $variant->id,
                'cantidad' => $request->quantity,
                'precio_unitario' => $variant->precio_venta,
                'importe' => $request->quantity * $variant->precio_venta,
                'producto_nombre_snapshot' => $variant->getVariantName(),
            ]);
        }

        $this->updateSaleTotals($sale);

        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Update item quantity
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $sale = $this->getCurrentSale();
        $item = $sale->items()->where('id', $itemId)->firstOrFail();
        $variant = $item->variant;

        // Check stock
        if ($variant->stock_actual < $request->quantity) {
            return response()->json(['error' => 'Stock insuficiente'], 422);
        }

        $item->cantidad = $request->quantity;
        $item->importe = $item->cantidad * $item->precio_unitario;
        $item->save();

        $this->updateSaleTotals($sale);

        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Remove item
    public function removeItem($itemId)
    {
        $sale = $this->getCurrentSale();
        $item = $sale->items()->where('id', $itemId)->firstOrFail();
        $item->delete();

        $this->updateSaleTotals($sale);

        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Assign Client
    public function assignClient(Request $request)
    {
        $request->validate(['client_id' => 'nullable|exists:clients,id']);

        $sale = $this->getCurrentSale();
        $sale->client_id = $request->client_id;
        $sale->save();

        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Cancel Sale (Clear Cart)
    public function cancelSale()
    {
        $sale = $this->getCurrentSale();
        $sale->items()->delete();
        $sale->client_id = null;
        $sale->notas = null;
        $this->updateSaleTotals($sale);

        return response()->json($this->formatSaleResponse($sale));
    }

    // API: Checkout
    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'amount_paid' => 'numeric|min:0',
        ]);

        $sale = $this->getCurrentSale();

        if ($sale->items()->count() === 0) {
            return response()->json(['error' => 'El carrito está vacío'], 422);
        }

        // Verify stock one last time
        foreach ($sale->items as $item) {
            if ($item->variant->stock_actual < $item->cantidad) {
                return response()->json(['error' => "Stock insuficiente para {$item->producto_nombre_snapshot}"], 422);
            }
        }

        DB::beginTransaction();
        try {
            $sale->metodo_pago = $request->payment_method;
            $sale->complete();
            DB::commit();

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'folio' => $sale->folio,
                'redirect' => route('sales.show', $sale->id)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar la venta: ' . $e->getMessage()], 500);
        }
    }

    // Helpers
    private function getCurrentSale()
    {
        // Find pending sale for current user/org or create new
        $sale = Sale::firstOrCreate(
            [
                'organization_id' => Auth::user()->organization_id,
                'user_id' => Auth::id(),
                'estatus' => 'pendiente'
            ],
            [
                'subtotal' => 0,
                'impuesto' => 0,
                'total' => 0
            ]
        );

        return $sale;
    }

    private function updateSaleTotals(Sale $sale)
    {
        $subtotal = $sale->items()->sum('importe');

        // Calculate tax - Assuming included or excluded?
        // Requirement said: "Sí, calcular IVA del 16%"
        // Usually in POS prices include tax or not. 
        // Let's assume prices are base prices and tax is added, OR prices are final.
        // Given "Calcular IVA", I'll assume it needs to be calculated on top or extracted.
        // For simplicity and common Mexican POS: Price usually includes VAT, but internal calculation requires breakdown.
        // Let's assume Price List is Net (before tax) for now, or we can make it configurable. 
        // BUT, usually in retail handling decimals is pain if we add tax. 
        // Let's check Product model... generic prices.
        // Let's assume: subtotal is sum of items. Tax is 0.16 * subtotal. Total = subtotal + tax.

        $tax = $subtotal * 0.16;
        $total = $subtotal + $tax;

        $sale->update([
            'subtotal' => $subtotal,
            'impuesto' => $tax,
            'total' => $total
        ]);
    }

    private function formatSaleResponse(Sale $sale)
    {
        $sale->refresh();
        $sale->load(['items', 'client']);
        return $sale;
    }
}
