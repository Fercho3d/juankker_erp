<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('organization_id', Auth::user()->organization_id)
            ->with(['category', 'brand', 'variants'])
            ->search($request->search)
            ->ofCategory($request->category_id)
            ->ofBrand($request->brand_id)
            ->ofType($request->tipo_producto)
            ->status($request->status);

        $products = $query->orderBy('nombre')->paginate(15)->withQueryString();

        $categories = Category::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $brands = Brand::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('products.index', compact('products', 'categories', 'brands'));
    }

    public function create()
    {
        $product = new Product();

        $categories = Category::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $brands = Brand::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $suppliers = Supplier::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get();

        $attributes = ProductAttribute::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->with('values')
            ->ordered()
            ->get();

        $unidadesMedida = $this->getUnidadesMedida();
        $codigosSat = $this->getCodigosSat();

        return view('products.form', compact('product', 'categories', 'brands', 'suppliers', 'attributes', 'unidadesMedida', 'codigosSat'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        // Remove variants from validated data as they are handled separately
        if (isset($validated['variants'])) {
            unset($validated['variants']);
        }

        DB::beginTransaction();
        try {
            // Create product
            $product = Product::create($validated);

            // Handle image upload
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('products', 'public');
                $product->update(['imagen' => $path]);
            }

            // Create variant(s)
            if ($product->tipo_producto === 'simple') {
                // For simple products, create single variant
                $product->variants()->create([
                    'sku' => $product->codigo,
                    'precio_venta' => $product->precio_venta,
                    'precio_compra' => $product->precio_compra,
                    'precio_mayoreo' => $product->precio_mayoreo,
                    'stock_actual' => $request->input('stock_inicial', 0),
                    'codigo_barras' => $request->input('codigo_barras'),
                    'activo' => true,
                ]);
            } else {
                // For variable products, create variants from request
                $variants = $request->input('variants', []);
                foreach ($variants as $variantData) {
                    $variant = $product->variants()->create([
                        'sku' => $variantData['sku'],
                        'codigo_barras' => $variantData['codigo_barras'] ?? null,
                        'precio_venta' => $variantData['precio_venta'] ?? $product->precio_venta,
                        'precio_compra' => $variantData['precio_compra'] ?? $product->precio_compra,
                        'precio_mayoreo' => $variantData['precio_mayoreo'] ?? $product->precio_mayoreo,
                        'stock_actual' => $variantData['stock_actual'] ?? 0,
                        'activo' => isset($variantData['activo']) ? (bool) $variantData['activo'] : true,
                    ]);

                    // Attach attribute values to variant
                    if (!empty($variantData['attribute_value_ids'])) {
                        $attributeValueIds = is_array($variantData['attribute_value_ids'])
                            ? $variantData['attribute_value_ids']
                            : explode(',', $variantData['attribute_value_ids']);
                        $variant->attributeValues()->attach($attributeValueIds);
                    }
                }
            }

            DB::commit();
            return redirect()->route('productos.index')->with('status', 'product-created');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al crear el producto: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Product $producto)
    {
        $this->authorizeProduct($producto);

        $product = $producto->load(['variants.attributeValues.attribute']);

        $categories = Category::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $brands = Brand::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $suppliers = Supplier::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get();

        $attributes = ProductAttribute::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->with('values')
            ->ordered()
            ->get();

        $unidadesMedida = $this->getUnidadesMedida();
        $codigosSat = $this->getCodigosSat();

        return view('products.form', compact('product', 'categories', 'brands', 'suppliers', 'attributes', 'unidadesMedida', 'codigosSat'));
    }

    public function update(Request $request, Product $producto)
    {
        $this->authorizeProduct($producto);

        $validated = $this->validateProduct($request, $producto->id);

        if (isset($validated['variants'])) {
            unset($validated['variants']);
        }

        DB::beginTransaction();
        try {
            $producto->update($validated);

            // Handle image upload
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('products', 'public');
                $producto->update(['imagen' => $path]);
            }

            // Update variants
            if ($producto->tipo_producto === 'simple') {
                $variant = $producto->variants()->first();
                if ($variant) {
                    $variant->update([
                        'sku' => $producto->codigo,
                        'precio_venta' => $producto->precio_venta,
                        'precio_compra' => $producto->precio_compra,
                        'precio_mayoreo' => $producto->precio_mayoreo,
                        'codigo_barras' => $request->input('codigo_barras'),
                    ]);
                }
            } else {
                // For variable products, sync variants
                $variants = $request->input('variants', []);
                $existingVariantIds = [];

                foreach ($variants as $variantData) {
                    if (isset($variantData['id']) && $variantData['id']) {
                        // Update existing variant
                        $variant = $producto->variants()->find($variantData['id']);
                        if ($variant) {
                            $variant->update([
                                'sku' => $variantData['sku'],
                                'codigo_barras' => $variantData['codigo_barras'] ?? null,
                                'precio_venta' => $variantData['precio_venta'] ?? $producto->precio_venta,
                                'precio_compra' => $variantData['precio_compra'] ?? $producto->precio_compra,
                                'precio_mayoreo' => $variantData['precio_mayoreo'] ?? $producto->precio_mayoreo,
                                'stock_actual' => $variantData['stock_actual'] ?? 0,
                                'activo' => isset($variantData['activo']) ? (bool) $variantData['activo'] : true,
                            ]);

                            // Sync attribute values
                            if (!empty($variantData['attribute_value_ids'])) {
                                $attributeValueIds = is_array($variantData['attribute_value_ids'])
                                    ? $variantData['attribute_value_ids']
                                    : explode(',', $variantData['attribute_value_ids']);
                                $variant->attributeValues()->sync($attributeValueIds);
                            }

                            $existingVariantIds[] = $variant->id;
                        }
                    } else {
                        // Create new variant
                        $variant = $producto->variants()->create([
                            'sku' => $variantData['sku'],
                            'codigo_barras' => $variantData['codigo_barras'] ?? null,
                            'precio_venta' => $variantData['precio_venta'] ?? $producto->precio_venta,
                            'precio_compra' => $variantData['precio_compra'] ?? $producto->precio_compra,
                            'precio_mayoreo' => $variantData['precio_mayoreo'] ?? $producto->precio_mayoreo,
                            'stock_actual' => $variantData['stock_actual'] ?? 0,
                            'activo' => isset($variantData['activo']) ? (bool) $variantData['activo'] : true,
                        ]);

                        if (!empty($variantData['attribute_value_ids'])) {
                            $attributeValueIds = is_array($variantData['attribute_value_ids'])
                                ? $variantData['attribute_value_ids']
                                : explode(',', $variantData['attribute_value_ids']);
                            $variant->attributeValues()->attach($attributeValueIds);
                        }

                        $existingVariantIds[] = $variant->id;
                    }
                }

                // Delete variants that were removed
                $producto->variants()->whereNotIn('id', $existingVariantIds)->delete();
            }

            DB::commit();
            return redirect()->route('productos.index')->with('status', 'product-updated');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al actualizar el producto: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Product $producto)
    {
        $this->authorizeProduct($producto);

        $producto->delete();

        return redirect()->route('productos.index')->with('status', 'product-deleted');
    }

    private function authorizeProduct(Product $product)
    {
        if ($product->organization_id !== Auth::user()->organization_id) {
            abort(403, 'No autorizado');
        }
    }

    private function validateProduct(Request $request, $ignoreId = null)
    {
        $organizationId = Auth::user()->organization_id;

        $rules = [
            'codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'codigo')
                    ->where('organization_id', $organizationId)
                    ->ignore($ignoreId),
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_producto' => 'required|in:simple,variable',
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId),
            ],
            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')
                    ->where('organization_id', $organizationId),
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')
                    ->where('organization_id', $organizationId),
            ],
            'unidad_medida' => 'required|in:pieza,kg,litro,metro,caja,paquete,servicio',
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'precio_mayoreo' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'codigo_sat' => 'nullable|string|max:10',
            'permite_decimales' => 'boolean',
            'activo' => 'boolean',
        ];

        // Variant validation
        if ($request->input('tipo_producto') === 'variable') {
            $rules['variants'] = 'required|array|min:1';

            foreach ($request->input('variants', []) as $key => $variant) {
                $rules["variants.{$key}.sku"] = [
                    'required',
                    'string',
                    'max:100',
                    'distinct',
                    Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null),
                ];
                $rules["variants.{$key}.precio_venta"] = 'required|numeric|min:0';
                $rules["variants.{$key}.stock_actual"] = 'nullable|integer|min:0';
            }
        }

        $customAttributes = [
            'variants.*.sku' => 'SKU de variante',
            'variants.*.precio_venta' => 'Precio de venta de variante',
            'variants.*.stock_actual' => 'Stock actual de variante',
        ];

        return $request->validate($rules, [], $customAttributes);
    }

    private function getUnidadesMedida()
    {
        return [
            'pieza' => 'Pieza',
            'kg' => 'Kilogramo',
            'litro' => 'Litro',
            'metro' => 'Metro',
            'caja' => 'Caja',
            'paquete' => 'Paquete',
            'servicio' => 'Servicio',
        ];
    }

    private function getCodigosSat()
    {
        return [
            '01010101' => '01010101 - No existe en el catálogo',
            '50202306' => '50202306 - Ropa',
            '50131600' => '50131600 - Calzado',
            '50202304' => '50202304 - Accesorios de vestir',
            '43191501' => '43191501 - Ordenadores portátiles',
            '43211503' => '43211503 - Teléfonos celulares',
            '50181501' => '50181501 - Alimentos preparados',
            '50202301' => '50202301 - Muebles',
        ];
    }
}
