<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductAttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductAttribute::where('organization_id', Auth::user()->organization_id)
            ->with('values')
            ->search($request->search)
            ->status($request->status);

        $attributes = $query->ordered()->paginate(15)->withQueryString();

        return view('product-attributes.index', compact('attributes'));
    }

    public function create()
    {
        $attribute = new ProductAttribute();

        return view('product-attributes.form', compact('attribute'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAttribute($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        $attribute = ProductAttribute::create($validated);

        return redirect()->route('atributos-producto.index')->with('status', 'attribute-created');
    }

    public function edit(ProductAttribute $atributo)
    {
        $this->authorizeAttribute($atributo);

        return view('product-attributes.form', ['attribute' => $atributo]);
    }

    public function update(Request $request, ProductAttribute $atributo)
    {
        $this->authorizeAttribute($atributo);

        $validated = $this->validateAttribute($request, $atributo->id);

        $atributo->update($validated);

        return redirect()->route('atributos-producto.index')->with('status', 'attribute-updated');
    }

    public function destroy(ProductAttribute $atributo)
    {
        $this->authorizeAttribute($atributo);

        $atributo->delete();

        return redirect()->route('atributos-producto.index')->with('status', 'attribute-deleted');
    }

    private function authorizeAttribute(ProductAttribute $attribute)
    {
        if ($attribute->organization_id !== Auth::user()->organization_id) {
            abort(403, 'No autorizado');
        }
    }

    private function validateAttribute(Request $request, $ignoreId = null)
    {
        $organizationId = Auth::user()->organization_id;

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_attributes', 'nombre')
                    ->where('organization_id', $organizationId)
                    ->ignore($ignoreId),
            ],
            'tipo' => 'required|in:select,color,text',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);
    }
}
