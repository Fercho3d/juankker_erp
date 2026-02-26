<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::where('organization_id', Auth::user()->organization_id)
            ->search($request->search)
            ->status($request->status);

        $brands = $query->orderBy('nombre')->paginate(15)->withQueryString();

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        $brand = new Brand();

        return view('brands.form', compact('brand'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateBrand($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        Brand::create($validated);

        return redirect()->route('marcas.index')->with('status', 'brand-created');
    }

    public function edit(Brand $marca)
    {
        $this->authorizeBrand($marca);

        return view('brands.form', ['brand' => $marca]);
    }

    public function update(Request $request, Brand $marca)
    {
        $this->authorizeBrand($marca);

        $validated = $this->validateBrand($request, $marca->id);

        $marca->update($validated);

        return redirect()->route('marcas.index')->with('status', 'brand-updated');
    }

    public function destroy(Brand $marca)
    {
        $this->authorizeBrand($marca);

        $marca->delete();

        return redirect()->route('marcas.index')->with('status', 'brand-deleted');
    }

    private function authorizeBrand(Brand $brand)
    {
        if ($brand->organization_id !== Auth::user()->organization_id) {
            abort(403, 'No autorizado');
        }
    }

    private function validateBrand(Request $request, $ignoreId = null)
    {
        $organizationId = Auth::user()->organization_id;

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'nombre')
                    ->where('organization_id', $organizationId)
                    ->ignore($ignoreId),
            ],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);
    }
}
