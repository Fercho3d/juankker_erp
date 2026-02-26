<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::where('organization_id', Auth::user()->organization_id)
            ->search($request->input('search'))
            ->ofType($request->input('tipo_persona'))
            ->status($request->input('activo'));

        $query->orderBy('razon_social');

        $suppliers = $query->paginate(15)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form', [
            'supplier' => new Supplier(),
            'estados' => ClientController::estados(),
            'regimenes' => ClientController::regimenesFiscales(),
            'usosCfdi' => ClientController::usosCfdi(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        Supplier::create($validated);

        return redirect()->route('proveedores.index')->with('status', 'supplier-created');
    }

    public function edit(Supplier $proveedore)
    {
        $this->authorizeSupplier($proveedore);

        return view('suppliers.form', [
            'supplier' => $proveedore,
            'estados' => ClientController::estados(),
            'regimenes' => ClientController::regimenesFiscales(),
            'usosCfdi' => ClientController::usosCfdi(),
        ]);
    }

    public function update(Request $request, Supplier $proveedore)
    {
        $this->authorizeSupplier($proveedore);

        $validated = $this->validateSupplier($request, $proveedore->id);
        $proveedore->update($validated);

        return redirect()->route('proveedores.index')->with('status', 'supplier-updated');
    }

    public function destroy(Supplier $proveedore)
    {
        $this->authorizeSupplier($proveedore);
        $proveedore->delete();

        return redirect()->route('proveedores.index')->with('status', 'supplier-deleted');
    }

    private function authorizeSupplier(Supplier $supplier)
    {
        if ($supplier->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }

    private function validateSupplier(Request $request, $ignoreId = null)
    {
        $orgId = Auth::user()->organization_id;
        $rfcUnique = 'unique:suppliers,rfc,' . $ignoreId . ',id,organization_id,' . $orgId;

        return $request->validate([
            'tipo_persona' => 'required|in:Física,Moral',
            'razon_social' => 'required|string|max:255',
            'rfc' => ['required', 'string', 'min:12', 'max:13', $rfcUnique],
            'curp' => 'nullable|string|size:18',
            'regimen_fiscal' => 'required|string|max:255',
            'uso_cfdi' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'contacto_nombre' => 'nullable|string|max:255',
            'calle' => 'required|string|max:255',
            'num_exterior' => 'required|string|max:20',
            'num_interior' => 'nullable|string|max:20',
            'colonia' => 'required|string|max:255',
            'codigo_postal' => 'required|string|size:5',
            'ciudad' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
        ]);
    }
}
