<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\CrmActivity;
use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ningún dato de una empresa debe verse ni tocarse desde otra.
 *
 * Desde la organización 1 se ataca a la 4: cada ruta que recibe un registro se
 * llama con uno ajeno, con datos que pasarían la validación, y el registro debe
 * quedar idéntico. Los listados y búsquedas no deben mostrar nada ajeno. Si se
 * agrega una ruta con un parámetro que esta prueba no conoce, falla: hay que
 * darle un blanco y pensar en su aislamiento.
 *
 * Corre sobre la base local: DatabaseTransactions deshace todo al terminar.
 */
class AislamientoEntreEmpresasTest extends TestCase
{
    use DatabaseTransactions;

    private const AJENA = 4;

    /** Parámetros que no son de una empresa: planes (globales) y el token de contraseña. */
    private const SIN_EMPRESA = ['plan', 'token'];

    private User $yo;

    /** @var array<string, Model> parámetro de ruta => registro de la empresa ajena */
    private array $blancos = [];

    /** @var list<string> textos que sólo existen en la empresa ajena */
    private array $secretos = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->yo = User::find(1);
        $this->prepararBlancos();
    }

    private function secreto(string $que): string
    {
        return $this->secretos[] = 'SECRETO-'.$que.'-'.uniqid();
    }

    private function prepararBlancos(): void
    {
        $org = self::AJENA;

        // Registros que ya tiene la empresa ajena, renombrados para reconocerlos sin error
        foreach (['cliente' => Client::class, 'proveedore' => Supplier::class] as $param => $modelo) {
            $r = $modelo::where('organization_id', $org)->firstOrFail();
            $r->update(['razon_social' => $this->secreto($param)]);
            $this->blancos[$param] = $r;
        }
        foreach (['categoria' => Category::class, 'marca' => Brand::class, 'producto' => Product::class] as $param => $modelo) {
            $r = $modelo::where('organization_id', $org)->firstOrFail();
            $r->update(['nombre' => $this->secreto($param)]);
            $this->blancos[$param] = $r;
        }
        $this->blancos['inventario'] = ProductVariant::where('product_id', $this->blancos['producto']->id)->firstOrFail();
        $this->blancos['sale'] = Sale::withoutGlobalScopes()->where('organization_id', $org)->firstOrFail();
        $this->blancos['item'] = SaleItem::whereIn('sale_id', Sale::withoutGlobalScopes()->where('organization_id', $org)->select('id'))->firstOrFail();
        $this->blancos['miembro'] = User::where('organization_id', $org)->firstOrFail();
        $this->blancos['miembro']->update(['name' => $this->secreto('miembro')]);

        // Lo que no tiene, se crea
        $atributo = ProductAttribute::create(['organization_id' => $org, 'nombre' => $this->secreto('atributo')]);
        $this->blancos['atributo'] = $atributo;
        $this->blancos['valor'] = ProductAttributeValue::create(['product_attribute_id' => $atributo->id, 'valor' => $this->secreto('valor')]);
        $etapa = CrmStage::paraOrganizacion($org)->first();
        $this->blancos['lead'] = Lead::create([
            'organization_id' => $org, 'stage_id' => $etapa->id, 'owner_id' => $this->blancos['miembro']->id,
            'nombre' => $this->secreto('lead'), 'empresa' => $this->secreto('empresa'), 'origen' => 'prospeccion', 'probabilidad' => 10,
            'proxima_accion_at' => now(),
        ]);
        $borrado = Lead::create(['organization_id' => $org, 'stage_id' => $etapa->id, 'nombre' => $this->secreto('papelera'), 'origen' => 'otro', 'probabilidad' => 5]);
        $borrado->delete();
        $this->blancos['id'] = $borrado;
        $this->blancos['actividad'] = CrmActivity::create([
            'organization_id' => $org, 'lead_id' => $this->blancos['lead']->id, 'tipo' => 'llamada',
            'descripcion' => $this->secreto('actividad'), 'programada_at' => now(),
        ]);
        $this->blancos['perfil'] = Role::paraOrganizacion($org)->firstWhere('es_admin', false);
        $this->blancos['invitacion'] = tap((new TeamInvitation([
            'organization_id' => $org, 'role_id' => $this->blancos['perfil']->id, 'email' => strtolower($this->secreto('correo')).'@otra.test',
        ]))->renovar())->save();
    }

    /** Estado completo del registro, para comparar antes y después (incluso si "se borró"). */
    private function estado(Model $m): ?array
    {
        $q = $m->newQueryWithoutScopes()->whereKey($m->getKey());

        return optional($q->first())->getAttributes();
    }

    /** Datos del propio registro ajeno con un cambio: si faltara la comprobación, se guardarían. */
    private function carga(string $nombreRuta, Model $blanco): array
    {
        $miEtapa = CrmStage::paraOrganizacion($this->yo->organization_id)->first();
        $miPerfil = Role::paraOrganizacion($this->yo->organization_id)->firstWhere('es_admin', false);

        $datos = collect($blanco->attributesToArray())
            ->except(['id', 'organization_id', 'created_at', 'updated_at', 'deleted_at', 'token', 'password'])
            ->merge([
                'nombre' => 'HACKEADO', 'razon_social' => 'HACKEADO', 'valor' => 'HACKEADO', 'empresa' => 'HACKEADO',
                'codigo' => 'HACK-'.uniqid(), 'category_id' => null, 'brand_id' => null, 'supplier_id' => null,
                'stage_id' => $miEtapa->id, 'role_id' => $miPerfil->id, 'owner_id' => $this->yo->id,
                'quantity' => 7, 'operation' => 'set', 'crm_alcance' => 'todos', 'permisos' => ['crm'], 'motivo' => 'otro',
                'activo' => 1,
            ]);

        if (str_contains($nombreRuta, 'actividad')) {
            $datos = $datos->merge(['tipo' => 'nota', 'descripcion' => 'HACKEADO']);
        }

        return $datos->all();
    }

    public function test_ninguna_ruta_ve_ni_toca_registros_de_otra_empresa(): void
    {
        $revisadas = 0;
        $fallas = [];

        foreach (Route::getRoutes() as $ruta) {
            $uri = $ruta->uri();
            $params = $ruta->parameterNames();
            if (! $params || array_intersect($params, self::SIN_EMPRESA)
                || preg_match('#^(superadmin|sanctum|_ignition|storage|unirse|invitacion)#', $uri)) {
                continue;
            }

            $sinBlanco = array_diff($params, array_keys($this->blancos));
            $this->assertEmpty($sinBlanco, "La ruta {$uri} tiene parámetros sin blanco: ".implode(', ', $sinBlanco).'. Agrégalos a prepararBlancos().');

            foreach (array_diff($ruta->methods(), ['HEAD']) as $metodo) {
                $valores = array_map(fn ($p) => $this->blancos[$p]->getKey(), array_combine($params, $params));
                $url = '/'.preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $valores[$m[1]], $uri);
                $antes = array_map(fn ($p) => $this->estado($this->blancos[$p]), array_combine($params, $params));

                $respuesta = $this->pedir($metodo, $url, $ruta->getName() ?? $uri, $this->blancos[end($params)]);
                $revisadas++;

                $codigo = $respuesta->getStatusCode();
                $cuerpo = (string) $respuesta->getContent();
                foreach ($params as $p) {
                    if ($this->estado($this->blancos[$p]) != $antes[$p]) {
                        $fallas[] = "{$metodo} {$uri}: MODIFICÓ el registro ajeno ({$p}) — HTTP {$codigo}";
                    }
                }
                if (! in_array($codigo, [403, 404], true) && ! ($codigo === 302 && session()->has('errors'))) {
                    $fallas[] = "{$metodo} {$uri}: respondió {$codigo} con un registro ajeno (se esperaba 403/404)";
                }
                foreach ($this->secretos as $s) {
                    if (str_contains($cuerpo, $s)) {
                        $fallas[] = "{$metodo} {$uri}: MOSTRÓ un dato ajeno ({$s})";
                    }
                }
            }
        }

        $this->assertGreaterThan(40, $revisadas, 'Se revisaron sospechosamente pocas rutas.');
        $this->assertSame([], $fallas, "Fugas entre empresas:\n".implode("\n", $fallas));
    }

    private function pedir(string $metodo, string $url, string $nombre, Model $blanco)
    {
        $this->app['auth']->forgetGuards();
        session()->forget('errors');
        str_starts_with($url, '/api/') ? Sanctum::actingAs($this->yo) : $this->actingAs($this->yo);
        $datos = $metodo === 'GET' ? [] : $this->carga($nombre, $blanco);

        return str_starts_with($url, '/api/')
            ? $this->json($metodo, $url, $datos)
            : $this->call($metodo, $url, $datos);
    }

    public function test_mi_atributo_con_un_valor_ajeno_tampoco(): void
    {
        $mio = ProductAttribute::create(['organization_id' => $this->yo->organization_id, 'nombre' => 'Mío']);
        $ajeno = $this->blancos['valor'];
        $antes = $this->estado($ajeno);

        $this->actingAs($this->yo)->put("/atributos-producto/{$mio->id}/valores/{$ajeno->id}", ['valor' => 'HACKEADO'])->assertForbidden();
        $this->actingAs($this->yo)->delete("/atributos-producto/{$mio->id}/valores/{$ajeno->id}")->assertForbidden();

        $this->assertEquals($antes, $this->estado($ajeno));
    }

    public function test_los_listados_y_busquedas_no_muestran_nada_ajeno(): void
    {
        $pantallas = ['/clientes', '/proveedores', '/productos', '/categorias', '/marcas', '/atributos-producto',
            '/inventario', '/ventas', '/pos', '/crm', '/crm/tablero', '/crm/papelera', '/crm/leads/nuevo', '/crm/importar',
            '/equipo', '/productos/create', '/clientes?search=SECRETO', '/productos?search=SECRETO', '/inventario?search=SECRETO',
            '/crm/tablero?search=SECRETO', '/crm/papelera?search=SECRETO', '/pos/search?q=SECRETO'];
        $api = ['/api/crm/leads', '/api/crm/leads?search=SECRETO', '/api/crm/pendientes', '/api/crm/etapas', '/api/crm/resumen'];

        $fugas = [];
        foreach ($pantallas as $url) {
            $this->app['auth']->forgetGuards();
            $cuerpo = (string) $this->actingAs($this->yo)->get($url)->getContent();
            foreach ($this->secretos as $s) {
                if (str_contains($cuerpo, $s)) {
                    $fugas[] = "{$url} mostró {$s}";
                }
            }
        }
        foreach ($api as $url) {
            $this->app['auth']->forgetGuards();
            Sanctum::actingAs($this->yo);
            $cuerpo = (string) $this->getJson($url)->getContent();
            foreach ($this->secretos as $s) {
                if (str_contains($cuerpo, $s)) {
                    $fugas[] = "{$url} mostró {$s}";
                }
            }
        }

        $this->assertSame([], $fugas, "Listados con datos de otra empresa:\n".implode("\n", $fugas));
    }

    /**
     * IDs ajenos metidos en el cuerpo del formulario (no en la URL): cada campo
     * que recibe un ID debe rechazar el de otra empresa.
     */
    public function test_los_ids_ajenos_en_formularios_se_rechazan(): void
    {
        $ajena = self::AJENA;
        $etapaAjena = CrmStage::paraOrganizacion($ajena)->first()->id;
        $perfilAjeno = $this->blancos['perfil']->id;
        $miembroAjeno = $this->blancos['miembro']->id;
        $miEtapa = CrmStage::paraOrganizacion($this->yo->organization_id)->first()->id;
        $miMiembro = User::create([
            'name' => 'Mío', 'email' => uniqid().'@prueba.test', 'password' => bcrypt('x'),
            'organization_id' => $this->yo->organization_id,
            'role_id' => Role::paraOrganizacion($this->yo->organization_id)->firstWhere('nombre', 'Vendedor')->id,
        ]);
        $miLead = Lead::create(['organization_id' => $this->yo->organization_id, 'stage_id' => $miEtapa, 'owner_id' => $this->yo->id,
            'nombre' => 'Mío', 'origen' => 'otro', 'probabilidad' => 5]);
        $lead = ['nombre' => 'X', 'origen' => 'prospeccion', 'probabilidad' => 10];

        $casos = [
            'prospecto con etapa ajena' => ['POST', '/crm/leads', $lead + ['stage_id' => $etapaAjena]],
            'prospecto con cliente ajeno' => ['POST', '/crm/leads', $lead + ['client_id' => $this->blancos['cliente']->id]],
            'prospecto con responsable ajeno' => ['POST', '/crm/leads', $lead + ['owner_id' => $miembroAjeno]],
            'alta masiva con etapa ajena' => ['POST', '/crm/importar', ['datos' => 'Empresa X', 'stage_id' => $etapaAjena]],
            'asignar a alguien de otra empresa' => ['POST', "/crm/leads/{$miLead->id}/asignar", ['owner_id' => $miembroAjeno]],
            'repartir a alguien de otra empresa' => ['POST', '/crm/asignar', ['owner_id' => $miembroAjeno]],
            'producto con categoría ajena' => ['POST', '/productos', $this->productoMinimo(['category_id' => $this->blancos['categoria']->id])],
            'producto con marca ajena' => ['POST', '/productos', $this->productoMinimo(['brand_id' => $this->blancos['marca']->id])],
            'producto con proveedor ajeno' => ['POST', '/productos', $this->productoMinimo(['supplier_id' => $this->blancos['proveedore']->id])],
            'categoría con padre ajeno' => ['POST', '/categorias', ['nombre' => 'X', 'parent_id' => $this->blancos['categoria']->id]],
            'invitar con perfil ajeno' => ['POST', '/equipo/invitaciones', ['email' => uniqid().'@prueba.test', 'role_id' => $perfilAjeno]],
            'darle a mi miembro un perfil ajeno' => ['PUT', "/equipo/miembros/{$miMiembro->id}", ['role_id' => $perfilAjeno]],
            'carrito con producto ajeno' => ['POST', '/pos/cart/add', ['variant_id' => $this->blancos['inventario']->id, 'quantity' => 1]],
            'venta con cliente ajeno' => ['POST', '/pos/cart/client', ['client_id' => $this->blancos['cliente']->id]],
        ];

        $fallas = [];
        foreach ($casos as $caso => [$metodo, $url, $datos]) {
            $this->app['auth']->forgetGuards();
            session()->forget('errors');
            $r = $this->actingAs($this->yo)->call($metodo, $url, $datos, [], [], $url === '/pos/cart/add' || $url === '/pos/cart/client' ? ['HTTP_ACCEPT' => 'application/json'] : []);
            $rechazado = in_array($r->getStatusCode(), [403, 404, 422], true) || session()->has('errors');
            if (! $rechazado) {
                $fallas[] = "{$caso}: se aceptó (HTTP {$r->getStatusCode()})";
            }
        }

        // Y que nada haya quedado colgado de un registro ajeno
        $this->assertFalse(Lead::where('organization_id', $this->yo->organization_id)->whereIn('stage_id', CrmStage::where('organization_id', $ajena)->select('id'))->exists());
        $this->assertFalse(Product::where('organization_id', $this->yo->organization_id)->whereIn('category_id', Category::where('organization_id', $ajena)->select('id'))->exists());
        $this->assertFalse(Sale::where('organization_id', $this->yo->organization_id)->whereIn('client_id', Client::where('organization_id', $ajena)->select('id'))->exists());
        $this->assertSame([], $fallas, "IDs ajenos aceptados:\n".implode("\n", $fallas));
    }

    public function test_un_producto_no_se_cuelga_valores_de_atributo_ajenos(): void
    {
        $mio = ProductAttribute::create(['organization_id' => $this->yo->organization_id, 'nombre' => 'Talla '.uniqid()]);
        $miValor = ProductAttributeValue::create(['product_attribute_id' => $mio->id, 'valor' => 'M']);
        $ajeno = $this->blancos['valor'];

        $this->actingAs($this->yo)->post('/productos', $this->productoMinimo([
            'tipo_producto' => 'variable',
            'variants' => [['sku' => 'V-'.uniqid(), 'precio_venta' => 10, 'attribute_value_ids' => [$miValor->id, $ajeno->id]]],
        ]))->assertSessionHasNoErrors();

        $variante = ProductVariant::where('organization_id', $this->yo->organization_id)->latest('id')->first();
        $this->assertSame([$miValor->id], $variante->attributeValues()->pluck('product_attribute_values.id')->all());
    }

    private function productoMinimo(array $extra = []): array
    {
        return $extra + ['codigo' => 'P-'.uniqid(), 'nombre' => 'Prueba', 'tipo_producto' => 'simple',
            'unidad_medida' => 'pieza', 'precio_venta' => 10, 'activo' => 1];
    }

    public function test_el_mismo_codigo_de_barras_sirve_en_dos_empresas(): void
    {
        $ajena = $this->blancos['inventario'];
        $ajena->update(['codigo_barras' => '7501055300075', 'sku' => 'SKU-COMPARTIDO']);

        $this->actingAs($this->yo)->post('/productos', [
            'codigo' => 'SKU-COMPARTIDO', 'nombre' => 'Refresco', 'tipo_producto' => 'simple', 'unidad_medida' => 'pieza',
            'precio_venta' => 20, 'codigo_barras' => '7501055300075', 'activo' => 1,
        ])->assertSessionHasNoErrors();

        $mia = ProductVariant::where('organization_id', $this->yo->organization_id)->where('codigo_barras', '7501055300075')->first();
        $this->assertNotNull($mia, 'La segunda empresa no pudo registrar un EAN que ya usa otra.');
        $this->assertSame('SKU-COMPARTIDO', $mia->sku);
    }
}
