<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::where('organization_id', Auth::user()->organization_id)
            ->with('parent')
            ->search($request->search)
            ->status($request->status);

        $categories = $query->orderBy('nombre')->paginate(15)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $category = new Category();
        $parentCategories = Category::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        return view('categories.form', compact('category', 'parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        Category::create($validated);

        return redirect()->route('categorias.index')->with('status', 'category-created');
    }

    public function edit(Category $categoria)
    {
        $this->authorizeCategory($categoria);

        $parentCategories = Category::where('organization_id', Auth::user()->organization_id)
            ->where('activo', true)
            ->whereNull('parent_id')
            ->where('id', '!=', $categoria->id)
            ->orderBy('nombre')
            ->get();

        return view('categories.form', [
            'category' => $categoria,
            'parentCategories' => $parentCategories
        ]);
    }

    public function update(Request $request, Category $categoria)
    {
        $this->authorizeCategory($categoria);

        $validated = $this->validateCategory($request, $categoria->id);

        $categoria->update($validated);

        return redirect()->route('categorias.index')->with('status', 'category-updated');
    }

    public function destroy(Category $categoria)
    {
        $this->authorizeCategory($categoria);

        $categoria->delete();

        return redirect()->route('categorias.index')->with('status', 'category-deleted');
    }

    private function authorizeCategory(Category $category)
    {
        if ($category->organization_id !== Auth::user()->organization_id) {
            abort(403, 'No autorizado');
        }
    }

    private function validateCategory(Request $request, $ignoreId = null)
    {
        $organizationId = Auth::user()->organization_id;

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'nombre')
                    ->where('organization_id', $organizationId)
                    ->ignore($ignoreId),
            ],
            'descripcion' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId),
            ],
            'activo' => 'boolean',
        ]);
    }
}
