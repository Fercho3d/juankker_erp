<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductAttributeValueController extends Controller
{
    public function index(ProductAttribute $atributo)
    {
        $this->authorizeAttribute($atributo);

        $values = $atributo->values()->ordered()->get();

        return view('product-attributes.values', [
            'attribute' => $atributo,
            'values' => $values
        ]);
    }

    public function store(Request $request, ProductAttribute $atributo)
    {
        $this->authorizeAttribute($atributo);

        $validated = $request->validate([
            'valor' => 'required|string|max:100',
            'codigo_color' => 'nullable|string|size:7',
            'orden' => 'nullable|integer|min:0',
        ]);

        $atributo->values()->create($validated);

        return redirect()->route('atributos-producto.valores.index', $atributo)
            ->with('status', 'value-created');
    }

    public function update(Request $request, ProductAttribute $atributo, ProductAttributeValue $valor)
    {
        $this->authorizeAttribute($atributo);
        $this->authorizeValue($valor, $atributo);

        $validated = $request->validate([
            'valor' => 'required|string|max:100',
            'codigo_color' => 'nullable|string|size:7',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);

        $valor->update($validated);

        return redirect()->route('atributos-producto.valores.index', $atributo)
            ->with('status', 'value-updated');
    }

    public function destroy(ProductAttribute $atributo, ProductAttributeValue $valor)
    {
        $this->authorizeAttribute($atributo);
        $this->authorizeValue($valor, $atributo);

        $valor->delete();

        return redirect()->route('atributos-producto.valores.index', $atributo)
            ->with('status', 'value-deleted');
    }

    private function authorizeAttribute(ProductAttribute $attribute)
    {
        if ($attribute->organization_id !== Auth::user()->organization_id) {
            abort(403, 'No autorizado');
        }
    }

    private function authorizeValue(ProductAttributeValue $value, ProductAttribute $attribute)
    {
        if ($value->product_attribute_id !== $attribute->id) {
            abort(403, 'No autorizado');
        }
    }
}
