<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::where('organization_id', Auth::user()->organization_id)
            ->search($request->input('search'))
            ->ofType($request->input('tipo_persona'))
            ->status($request->input('activo'));

        $query->orderBy('razon_social');

        $clients = $query->paginate(15)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.form', [
            'client' => new Client(),
            'estados' => self::estados(),
            'regimenes' => self::regimenesFiscales(),
            'usosCfdi' => self::usosCfdi(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateClient($request);
        $validated['organization_id'] = Auth::user()->organization_id;

        Client::create($validated);

        return redirect()->route('clientes.index')->with('status', 'client-created');
    }

    public function edit(Client $cliente)
    {
        $this->authorizeClient($cliente);

        return view('clients.form', [
            'client' => $cliente,
            'estados' => self::estados(),
            'regimenes' => self::regimenesFiscales(),
            'usosCfdi' => self::usosCfdi(),
        ]);
    }

    public function update(Request $request, Client $cliente)
    {
        $this->authorizeClient($cliente);

        $validated = $this->validateClient($request, $cliente->id);
        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('status', 'client-updated');
    }

    public function destroy(Client $cliente)
    {
        $this->authorizeClient($cliente);
        $cliente->delete();

        return redirect()->route('clientes.index')->with('status', 'client-deleted');
    }

    private function authorizeClient(Client $client)
    {
        if ($client->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }

    private function validateClient(Request $request, $ignoreId = null)
    {
        $orgId = Auth::user()->organization_id;
        $rfcUnique = 'unique:clients,rfc,' . $ignoreId . ',id,organization_id,' . $orgId;

        return $request->validate([
            'tipo_persona' => 'required|in:Física,Moral',
            'razon_social' => 'required|string|max:255',
            'rfc' => ['required', 'string', 'min:12', 'max:13', $rfcUnique],
            'curp' => 'nullable|string|size:18',
            'regimen_fiscal' => 'required|string|max:255',
            'uso_cfdi' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:20',
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

    public static function estados(): array
    {
        return [
            'Aguascalientes',
            'Baja California',
            'Baja California Sur',
            'Campeche',
            'Chiapas',
            'Chihuahua',
            'Ciudad de México',
            'Coahuila',
            'Colima',
            'Durango',
            'Estado de México',
            'Guanajuato',
            'Guerrero',
            'Hidalgo',
            'Jalisco',
            'Michoacán',
            'Morelos',
            'Nayarit',
            'Nuevo León',
            'Oaxaca',
            'Puebla',
            'Querétaro',
            'Quintana Roo',
            'San Luis Potosí',
            'Sinaloa',
            'Sonora',
            'Tabasco',
            'Tamaulipas',
            'Tlaxcala',
            'Veracruz',
            'Yucatán',
            'Zacatecas',
        ];
    }

    public static function regimenesFiscales(): array
    {
        return [
            '601 - General de Ley Personas Morales',
            '603 - Personas Morales con Fines no Lucrativos',
            '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606 - Arrendamiento',
            '608 - Demás ingresos',
            '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
            '611 - Ingresos por Dividendos (socios y accionistas)',
            '612 - Personas Físicas con Actividades Empresariales y Profesionales',
            '614 - Ingresos por intereses',
            '616 - Sin obligaciones fiscales',
            '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
            '621 - Incorporación Fiscal',
            '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            '623 - Opcional para Grupos de Sociedades',
            '624 - Coordinados',
            '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            '626 - Régimen Simplificado de Confianza',
        ];
    }

    public static function usosCfdi(): array
    {
        return [
            'G01 - Adquisición de mercancías',
            'G02 - Devoluciones, descuentos o bonificaciones',
            'G03 - Gastos en general',
            'I01 - Construcciones',
            'I02 - Mobiliario y equipo de oficina por inversiones',
            'I03 - Equipo de transporte',
            'I04 - Equipo de computo y accesorios',
            'I05 - Dados, troqueles, moldes, matrices y herramental',
            'I06 - Comunicaciones telefónicas',
            'I07 - Comunicaciones satelitales',
            'I08 - Otra maquinaria y equipo',
            'D01 - Honorarios médicos, dentales y gastos hospitalarios',
            'D02 - Gastos médicos por incapacidad o discapacidad',
            'D03 - Gastos funerales',
            'D04 - Donativos',
            'D05 - Intereses reales efectivamente pagados por créditos hipotecarios',
            'D06 - Aportaciones voluntarias al SAR',
            'D07 - Primas por seguros de gastos médicos',
            'D08 - Gastos de transportación escolar obligatoria',
            'D09 - Depósitos en cuentas para el ahorro, primas de pensiones',
            'D10 - Pagos por servicios educativos (colegiaturas)',
            'P01 - Por definir',
            'S01 - Sin efectos fiscales',
            'CP01 - Pagos',
            'CN01 - Nómina',
        ];
    }
}
