<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $search = $request->input('search');

        $variants = ProductVariant::query()
            ->whereHas('product', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($search, function ($query, $search) {
                $query->search($search);
            })
            ->with(['product'])
            ->paginate(20);

        return view('inventory.index', compact('variants', 'search'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
            'operation' => 'required|in:add,subtract,set',
            'notes' => 'nullable|string|max:255',
        ]);

        $variant = ProductVariant::findOrFail($id);

        // Check organization
        if ($variant->product->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $quantity = $request->quantity;
        $currentStock = $variant->stock_actual;
        $newStock = $currentStock;

        switch ($request->operation) {
            case 'add':
                $newStock = $currentStock + $quantity;
                break;
            case 'subtract':
                $newStock = max(0, $currentStock - $quantity);
                break;
            case 'set':
                $newStock = max(0, $quantity);
                break;
        }

        $variant->stock_actual = $newStock;
        $variant->save();

        // TODO: Record movement history (future enhancement)

        return back()->with('success', 'Stock actualizado correctamente.');
    }
}
