<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use App\Models\CrmStage;
use App\Models\Declaracion;
use App\Models\Factura;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SatSolicitud;
use App\Models\Supplier;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\DeclaracionSat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cuenta de PRUEBA prellenada: proveedor de maquinaria industrial
 * enfocada a la industria automotriz (robots, prensas, soldadura,
 * fundición a presión, CNC, metrología, pintura, logística).
 *
 * Acceso: demo@juankker.com / Demo1234
 *
 * Se puede correr las veces que sea: las fechas se recalculan contra hoy para que
 * la agenda y los reportes se vean al día. Los correos de prospectos e
 * invitaciones son @example.com para que un clic en "Enviar" no llegue a nadie.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026);

        $org = Organization::updateOrCreate(
            ['name' => 'Maquinaria Automotriz del Bajío S.A. de C.V.'],
            [
                'plan' => 'profesional',
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addYear(),
                'environment' => 'produccion',
                'is_active' => true,
            ]
        );

        if ($plan = Plan::where('key', 'profesional')->first()) {
            $org->applyPlan($plan);
            $org->save();
        }

        $owner = User::updateOrCreate(
            ['email' => 'demo@juankker.com'],
            [
                'name' => 'Ricardo Salinas (Demo)',
                'password' => Hash::make('Demo1234'),
                'organization_id' => $org->id,
                'email_verified_at' => now(),
                'last_login_at' => now()->subDays(1),
                'login_count' => 14,
            ]
        );
        $org->update(['owner_id' => $owner->id]);

        // Segundo usuario (operador) para estadísticas de uso.
        $laura = User::updateOrCreate(
            ['email' => 'operador@juankker.com'],
            [
                'name' => 'Laura Méndez (Ventas)',
                'password' => Hash::make('Demo1234'),
                'organization_id' => $org->id,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(5),
                'login_count' => 33,
            ]
        );

        $categories = $this->seedCategories($org);
        $brands = $this->seedBrands($org);
        $suppliers = $this->seedSuppliers($org);
        $clients = $this->seedClients($org);
        $products = $this->seedProducts($org, $categories, $brands, $suppliers);
        $products = array_merge($products, $this->seedProductosConVariantes($org, $categories, $brands, $suppliers));
        $this->seedSales($org, [$owner, $laura], $clients, $products);
        $vendedor = $this->seedEquipo($org, $owner, $laura);
        $this->seedCrm($org, $owner, $laura, $vendedor, $clients);
        $this->seedContabilidad($owner, $clients);

        $this->command?->info("Demo: {$org->name} · ".count($products).' productos · '.count($clients).' clientes.');
    }

    private function seedCategories(Organization $org): array
    {
        $names = [
            'Robótica y Automatización' => 'Robots articulados y celdas automatizadas de línea.',
            'Prensas y Estampado' => 'Prensas hidráulicas y mecánicas para carrocería.',
            'Soldadura' => 'Equipos de soldadura por puntos, MIG/MAG y robotizada.',
            'Fundición a Presión' => 'Inyección de aluminio y zamak (die casting).',
            'Maquinado CNC' => 'Centros de maquinado y tornos CNC.',
            'Metrología y Calidad' => 'CMM, escáneres 3D y bancos de prueba.',
            'Pintura y Recubrimiento' => 'Cabinas electrostáticas y hornos de curado.',
            'Logística y Manejo de Materiales' => 'Transportadores, grúas y montacargas.',
            'Consumibles y Refacciones' => 'Lo que se compra cada mes para mantener la línea trabajando.',
        ];

        $out = [];
        foreach ($names as $nombre => $desc) {
            $out[$nombre] = Category::updateOrCreate(
                ['organization_id' => $org->id, 'nombre' => $nombre],
                ['descripcion' => $desc, 'activo' => true]
            );
        }

        return $out;
    }

    private function seedBrands(Organization $org): array
    {
        $out = [];
        foreach (['FANUC', 'KUKA', 'ABB', 'Yaskawa Motoman', 'Siemens', 'Bosch Rexroth', 'Haas', 'Mazak', 'Zeiss', 'Atlas Copco', 'Schuler', 'Dürr', 'Lincoln Electric'] as $n) {
            $out[$n] = Brand::updateOrCreate(
                ['organization_id' => $org->id, 'nombre' => $n],
                ['activo' => true]
            );
        }

        return $out;
    }

    private function seedSuppliers(Organization $org): array
    {
        $data = [
            ['Robótica Industrial de México S.A. de C.V.', 'RIM120534AB2', 'ventas@roboticaindustrial.mx', 'Ing. Jorge Ballesteros', 'Parque Industrial Querétaro', 'Querétaro', 'Querétaro', '76220'],
            ['Automatización y Prensas del Norte S.A.', 'APN180712QK9', 'contacto@apnorte.com', 'Lic. Marisol Duarte', 'Av. Industrias', 'Monterrey', 'Nuevo León', '64500'],
            ['Herramentales CNC Bajío S. de R.L.', 'HCB150920TT4', 'ventas@cncbajio.mx', 'Ing. Pablo Reséndiz', 'Blvd. Aeropuerto', 'Silao', 'Guanajuato', '36275'],
            ['Consumibles de Soldadura del Centro S.A. de C.V.', 'CSC140605LM8', 'pedidos@soldaduracentro.example.com', 'Sra. Elena Cervantes', 'Av. Siglo XXI', 'Aguascalientes', 'Aguascalientes', '20290'],
            ['Lubricantes y Filtros Industriales S.A.', 'LFI110318RS5', 'ventas@lubrifiltros.example.com', 'Lic. Tomás Aguilar', 'Carretera Panamericana', 'León', 'Guanajuato', '37530'],
        ];

        $out = [];
        foreach ($data as [$rs, $rfc, $email, $contacto, $calle, $ciudad, $estado, $cp]) {
            $out[] = Supplier::updateOrCreate(
                ['organization_id' => $org->id, 'rfc' => $rfc],
                [
                    'tipo_persona' => 'Moral', 'razon_social' => $rs,
                    'regimen_fiscal' => '601', 'uso_cfdi' => 'G03',
                    'email' => $email, 'telefono' => '4421234567', 'contacto_nombre' => $contacto,
                    'calle' => $calle, 'num_exterior' => '100', 'colonia' => 'Centro Industrial',
                    'codigo_postal' => $cp, 'ciudad' => $ciudad, 'estado' => $estado, 'activo' => true,
                ]
            );
        }

        return $out;
    }

    private function seedClients(Organization $org): array
    {
        $data = [
            ['Ensambladora Automotriz Aguascalientes S.A. de C.V.', 'EAA160223HB1', 'compras@ensaguas.mx', 'Aguascalientes', 'Aguascalientes', '20340'],
            ['Autopartes y Estampados del Centro S.A.', 'AEC170815JK3', 'proveeduria@autopartescentro.mx', 'Celaya', 'Guanajuato', '38010'],
            ['Componentes Metálicos TIER-1 S. de R.L.', 'CMT190410PP7', 'planta@tier1metal.mx', 'San Luis Potosí', 'San Luis Potosí', '78395'],
            ['Forjas y Maquinados de Querétaro S.A. de C.V.', 'FMQ130722GH6', 'compras@forjasqro.example.com', 'Querétaro', 'Querétaro', '76120'],
            ['Arneses Eléctricos del Norte S.A. de C.V.', 'AEN180214DF2', 'compras@arnesesnorte.example.com', 'Saltillo', 'Coahuila', '25900'],
            ['Plásticos Inyectados del Bajío S. de R.L.', 'PIB160930WX4', 'compras@plasticosbajio.example.com', 'Irapuato', 'Guanajuato', '36800'],
            ['Suspensiones y Frenos de Puebla S.A.', 'SFP120508NB9', 'compras@sufrepuebla.example.com', 'Puebla', 'Puebla', '72300'],
            ['Asientos Automotrices de Silao S.A. de C.V.', 'AAS170126VC3', 'compras@asientossilao.example.com', 'Silao', 'Guanajuato', '36100'],
        ];

        $out = [];
        foreach ($data as [$rs, $rfc, $email, $ciudad, $estado, $cp]) {
            $out[] = Client::updateOrCreate(
                ['organization_id' => $org->id, 'rfc' => $rfc],
                [
                    'tipo_persona' => 'Moral', 'razon_social' => $rs,
                    'regimen_fiscal' => '601', 'uso_cfdi' => 'G03',
                    'email' => $email, 'telefono' => '449555'.sprintf('%04d', crc32($rfc) % 10000),
                    'calle' => 'Av. de la Convención', 'num_exterior' => '1500', 'colonia' => 'Zona Industrial',
                    'codigo_postal' => $cp, 'ciudad' => $ciudad, 'estado' => $estado, 'activo' => true,
                ]
            );
        }

        return $out;
    }

    /**
     * @return Product[]
     */
    private function seedProducts(Organization $org, array $cats, array $brands, array $suppliers): array
    {
        // [codigo, nombre, categoria, marca, unidad, compra, venta, mayoreo, stock, sat, descripcion]
        $rows = [
            ['ROB-FA-2000', 'Robot articulado FANUC R-2000iC/210F', 'Robótica y Automatización', 'FANUC', 2350000, 2890000, 2750000, 6, '25101500', 'Robot de 6 ejes, 210 kg de carga, para manejo de material y soldadura de carrocería.'],
            ['ROB-KK-210', 'Robot KUKA KR 210 R2700 extra', 'Robótica y Automatización', 'KUKA', 2180000, 2680000, 2500000, 4, '25101500', 'Robot industrial de 6 ejes, alcance 2700 mm, línea de ensamble automotriz.'],
            ['ROB-ABB-6700', 'Robot ABB IRB 6700-235/2.65', 'Robótica y Automatización', 'ABB', 2250000, 2790000, 2650000, 3, '25101500', 'Robot de alta carga para estampado y manejo de piezas grandes.'],
            ['CEL-YM-SPOT', 'Celda de soldadura por puntos robotizada', 'Soldadura', 'Yaskawa Motoman', 3100000, 3850000, 3600000, 2, '23271700', 'Celda llave en mano con robot Motoman y pinza servo para spot welding.'],
            ['SOL-MIG-500', 'Sistema de soldadura MIG/MAG robotizado 500A', 'Soldadura', 'ABB', 620000, 795000, 745000, 8, '23271700', 'Fuente de poder sinérgica 500 A con alimentador y antorcha refrigerada.'],
            ['PRN-SCH-400', 'Prensa mecánica progresiva Schuler 400 ton', 'Prensas y Estampado', 'Schuler', 5400000, 6650000, 6300000, 2, '23181500', 'Prensa de estampado progresivo para paneles de carrocería.'],
            ['PRN-HID-200', 'Prensa hidráulica 200 ton doble acción', 'Prensas y Estampado', 'Bosch Rexroth', 1850000, 2350000, 2200000, 3, '23181500', 'Prensa hidráulica para embutido profundo de lámina.'],
            ['DIE-AL-800', 'Máquina de fundición a presión de aluminio 800 ton', 'Fundición a Presión', 'Bosch Rexroth', 6900000, 8400000, 8000000, 1, '23151600', 'Celda de inyección de aluminio para monoblocks y componentes de motor.'],
            ['CNC-HAAS-VF4', 'Centro de maquinado vertical Haas VF-4SS', 'Maquinado CNC', 'Haas', 1450000, 1850000, 1720000, 5, '23241600', 'CNC vertical de alta velocidad, husillo 12000 rpm, para herramental y moldes.'],
            ['CNC-MAZ-QT', 'Torno CNC Mazak QUICK TURN 250', 'Maquinado CNC', 'Mazak', 1680000, 2100000, 1980000, 4, '23241700', 'Torno CNC para ejes y flechas de transmisión automotriz.'],
            ['CMM-ZE-PRO', 'Máquina de medición por coordenadas Zeiss CONTURA', 'Metrología y Calidad', 'Zeiss', 1980000, 2480000, 2350000, 3, '41111900', 'CMM tipo puente para control dimensional de piezas críticas.'],
            ['SCN-ZE-3D', 'Escáner láser 3D de metrología ZEISS T-SCAN', 'Metrología y Calidad', 'Zeiss', 890000, 1150000, 1080000, 6, '41111900', 'Escaneo 3D de superficies para ingeniería inversa y calidad.'],
            ['CAB-DUR-PAINT', 'Cabina de pintura electrostática Dürr', 'Pintura y Recubrimiento', 'Dürr', 2600000, 3250000, 3050000, 2, '24101600', 'Cabina presurizada con aplicadores electrostáticos para acabado automotriz.'],
            ['HOR-DUR-CURE', 'Horno de curado de pintura por convección', 'Pintura y Recubrimiento', 'Dürr', 1750000, 2200000, 2050000, 2, '24101600', 'Horno túnel para polimerizado de recubrimientos.'],
            ['CNV-SIE-ROLL', 'Transportador de rodillos motorizado 12 m', 'Logística y Manejo de Materiales', 'Siemens', 320000, 430000, 400000, 12, '24101500', 'Transportador modular con control Siemens para línea de ensamble.'],
            ['GRU-SIE-10T', 'Puente grúa birriel 10 toneladas', 'Logística y Manejo de Materiales', 'Siemens', 1250000, 1600000, 1500000, 3, '24101600', 'Grúa viajera de 10 t para manejo de troqueles y maquinaria pesada.'],
            ['CMP-AC-GA75', 'Compresor de tornillo Atlas Copco GA 75', 'Logística y Manejo de Materiales', 'Atlas Copco', 480000, 640000, 595000, 7, '40151500', 'Compresor de aire de 75 kW con secador para planta.'],
            ['TRQ-BR-DC', 'Sistema de atornillado DC controlado Bosch Rexroth', 'Robótica y Automatización', 'Bosch Rexroth', 210000, 289000, 265000, 20, '27112700', 'Husillos de torque de corriente directa con trazabilidad para ensamble.'],
            ['PUN-MIG-12', 'Punta de contacto MIG 1.2 mm (caja 25 pzas)', 'Consumibles y Refacciones', 'Lincoln Electric', 480, 720, 650, 34, '23271700', 'Punta de cobre para antorcha robotizada.'],
            ['ELE-SPOT-16', 'Electrodo de soldadura por puntos F16', 'Consumibles y Refacciones', 'Yaskawa Motoman', 95, 165, 145, 4, '23271700', 'Electrodo de cobre-cromo-circonio para pinza servo.'],
            ['CAS-SOL-AUTO', 'Careta de soldar fotosensible', 'Consumibles y Refacciones', 'Lincoln Electric', 1850, 2790, 2500, 15, '46181700', 'Oscurecimiento automático, grado 9 a 13.'],
            ['ACE-HID-68', 'Aceite hidráulico ISO 68 (cubeta 19 L)', 'Consumibles y Refacciones', 'Bosch Rexroth', 1150, 1690, 1520, 7, '15121500', 'Para prensas y unidades hidráulicas.'],
            ['FIL-GA-75', 'Kit de filtros para compresor GA 75', 'Consumibles y Refacciones', 'Atlas Copco', 6200, 8900, 8200, 12, '40161500', 'Filtro de aire, aceite y separador.'],
        ];

        $out = [];
        foreach ($rows as [$codigo, $nombre, $cat, $marca, $compra, $venta, $mayoreo, $stock, $sat, $desc]) {
            $product = Product::updateOrCreate(
                ['organization_id' => $org->id, 'codigo' => $codigo],
                [
                    'category_id' => $cats[$cat]->id ?? null,
                    'brand_id' => $brands[$marca]->id ?? null,
                    'supplier_id' => $suppliers[array_rand($suppliers)]->id,
                    'nombre' => $nombre,
                    'descripcion' => $desc,
                    'unidad_medida' => 'pieza',
                    'tipo_producto' => 'simple',
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'precio_mayoreo' => $mayoreo,
                    'stock_minimo' => $compra < 50000 ? 10 : 1,
                    'codigo_sat' => $sat,
                    'activo' => true,
                ]
            );

            $product->variants()->updateOrCreate(
                ['sku' => $codigo],
                [
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'precio_mayoreo' => $mayoreo,
                    'stock_actual' => $stock,
                    'activo' => true,
                ]
            );

            $out[] = $product;
        }

        return $out;
    }

    /**
     * Productos con variantes, para enseñar atributos y el selector del punto de venta.
     *
     * @return Product[]
     */
    private function seedProductosConVariantes(Organization $org, array $cats, array $brands, array $suppliers): array
    {
        // [atributo, codigo, nombre, marca, sat, descripcion, [valor, compra, venta, mayoreo, stock]]
        $rows = [
            ['Diámetro', 'MIC-ER70', 'Microalambre MIG ER70S-6 (rollo 15 kg)', 'Lincoln Electric', '23271700', 'Alambre cobrizado para soldadura de carrocería.', [
                ['0.9 mm', 620, 890, 810, 42], ['1.0 mm', 630, 905, 825, 18], ['1.2 mm', 640, 920, 840, 6],
            ]],
            ['Talla', 'GUA-SOL', 'Guantes de soldador de carnaza', 'Lincoln Electric', '46181500', 'Par de guantes con forro de algodón.', [
                ['Chica', 145, 240, 210, 25], ['Mediana', 145, 240, 210, 60], ['Grande', 150, 250, 220, 3],
            ]],
        ];

        $out = [];
        foreach ($rows as [$atributo, $codigo, $nombre, $marca, $sat, $desc, $variantes]) {
            $attr = ProductAttribute::updateOrCreate(
                ['organization_id' => $org->id, 'nombre' => $atributo],
                ['tipo' => 'select', 'orden' => 0, 'activo' => true]
            );
            $product = Product::updateOrCreate(
                ['organization_id' => $org->id, 'codigo' => $codigo],
                [
                    'category_id' => $cats['Consumibles y Refacciones']->id,
                    'brand_id' => $brands[$marca]->id,
                    'supplier_id' => $suppliers[array_rand($suppliers)]->id,
                    'nombre' => $nombre,
                    'descripcion' => $desc,
                    'unidad_medida' => 'pieza',
                    'tipo_producto' => 'variable',
                    'precio_compra' => $variantes[0][1],
                    'precio_venta' => $variantes[0][2],
                    'precio_mayoreo' => $variantes[0][3],
                    'stock_minimo' => 10,
                    'codigo_sat' => $sat,
                    'activo' => true,
                ]
            );

            foreach ($variantes as $i => [$valor, $compra, $venta, $mayoreo, $stock]) {
                $valorAttr = $attr->values()->updateOrCreate(['valor' => $valor], ['orden' => $i, 'activo' => true]);
                $product->variants()->updateOrCreate(
                    ['sku' => $codigo.'-'.Str::slug($valor)],
                    ['precio_compra' => $compra, 'precio_venta' => $venta, 'precio_mayoreo' => $mayoreo, 'stock_actual' => $stock, 'activo' => true]
                )->attributeValues()->sync([$valorAttr->id]);
            }
            $out[] = $product;
        }

        return $out;
    }

    /**
     * Seis meses de ventas: consumibles casi diario y una máquina de vez en cuando.
     * El carrito que haya quedado abierto se limpia para que el punto de venta arranque vacío.
     */
    private function seedSales(Organization $org, array $usuarios, array $clients, array $products): void
    {
        Sale::where('organization_id', $org->id)->where('estatus', 'pendiente')->delete();

        $maquinas = array_values(array_filter($products, fn ($p) => $p->precio_venta >= 200000));
        $consumibles = array_values(array_filter($products, fn ($p) => $p->precio_venta < 200000));

        foreach (range(1, 45) as $i) {
            $esMaquina = $i % 9 === 0;
            $renglones = $esMaquina ? [$maquinas[array_rand($maquinas)]] : array_map(
                fn ($k) => $consumibles[$k],
                (array) array_rand($consumibles, mt_rand(1, 3))
            );
            // Las dos últimas caen hoy para que el historial se vea al día.
            $fecha = now()->subDays($i > 43 ? 0 : (int) round((44 - $i) * 4))->setTime(mt_rand(9, 13), mt_rand(0, 59));

            $sale = Sale::updateOrCreate(
                ['organization_id' => $org->id, 'folio' => 'V-'.(1000 + $i)],
                [
                    'user_id' => $usuarios[$i % 2]->id,
                    'client_id' => $esMaquina || $i % 3 ? $clients[array_rand($clients)]->id : null,
                    'estatus' => $i % 17 === 0 ? 'cancelada' : 'completada',
                    'metodo_pago' => $esMaquina ? 'transferencia' : ['efectivo', 'tarjeta', 'transferencia'][$i % 3],
                ]
            );
            $sale->items()->delete();

            $subtotal = 0;
            foreach ($renglones as $product) {
                $variantes = $product->variants()->get();
                $variant = $variantes[mt_rand(0, $variantes->count() - 1)];
                $cantidad = $esMaquina ? 1 : mt_rand(1, 6);
                $importe = (float) $variant->precio_venta * $cantidad;
                $subtotal += $importe;
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variant->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $variant->precio_venta,
                    'importe' => $importe,
                    'producto_nombre_snapshot' => $product->nombre,
                ]);
            }

            $impuesto = round($subtotal * 0.16, 2);
            $sale->forceFill([
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $subtotal + $impuesto,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ])->save();
        }
    }

    /** Perfiles, miembros y una invitación pendiente. Devuelve al vendedor de "sólo sus prospectos". */
    private function seedEquipo(Organization $org, User $owner, User $laura): User
    {
        $perfiles = Role::paraOrganizacion($org->id)->keyBy('nombre');
        $owner->update(['role_id' => $perfiles['Administrador']->id]);
        $laura->update(['role_id' => $perfiles['Gerente de ventas']->id]);

        $miembros = [];
        foreach ([
            ['diego.ortega@example.com', 'Diego Ortega', 'Vendedor', 26, 3],
            ['sofia.ramos@example.com', 'Sofía Ramos', 'Punto de venta', 58, 1],
            ['monica.ibarra@example.com', 'Mónica Ibarra', 'Almacén', 19, 8],
        ] as [$email, $nombre, $perfil, $accesos, $horas]) {
            $miembros[$perfil] = User::updateOrCreate(['email' => $email], [
                'name' => $nombre,
                'password' => Hash::make('Demo1234'),
                'organization_id' => $org->id,
                'role_id' => $perfiles[$perfil]->id,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours($horas),
                'login_count' => $accesos,
            ]);
        }

        TeamInvitation::updateOrCreate(
            ['organization_id' => $org->id, 'email' => 'nuevo.vendedor@example.com'],
            ['role_id' => $perfiles['Vendedor']->id, 'invited_by' => $owner->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(5), 'accepted_at' => null]
        );

        return $miembros['Vendedor'];
    }

    /** Embudo con prospectos en cada etapa, agenda de hoy, bitácora, correos y respuestas. */
    private function seedCrm(Organization $org, User $owner, User $laura, User $vendedor, array $clients): void
    {
        $etapas = CrmStage::paraOrganizacion($org->id)->keyBy('nombre');
        CrmActivity::where('organization_id', $org->id)->delete();
        CrmBorrador::where('organization_id', $org->id)->delete();

        // [empresa, contacto, etapa, responsable, giro, sector, personal, municipio, valor, mensual, %, origen, próxima acción, días a la acción, días sin contacto]
        $rows = [
            ['Troqueles Finos de Aguascalientes', 'Ing. Marcos Delgado', 'Nuevo', null, 'Fabricación de troqueles', 'Manufactura', 51, 'Aguascalientes', 1850000, 0, 10, 'prospeccion', null, null, null],
            ['Tornillería Automotriz San Marcos', 'Lic. Andrea Vargas', 'Nuevo', $vendedor, 'Fabricación de tornillos', 'Manufactura', 31, 'Aguascalientes', 640000, 0, 10, 'prospeccion', 'Primera llamada', 0, null],
            ['Recubrimientos Metálicos de Jesús María', 'Ing. Óscar Luna', 'Nuevo', null, 'Recubrimientos metálicos', 'Manufactura', 101, 'Jesús María', 3250000, 0, 15, 'sitio_web', null, null, null],
            ['Moldes y Herramentales Calvillo', 'Sra. Patricia Rangel', 'Nuevo', $vendedor, 'Fabricación de moldes', 'Manufactura', 11, 'Calvillo', 1720000, 0, 10, 'prospeccion', null, null, null],
            ['Autopartes Rincón de Romos', 'Ing. Raúl Macías', 'Contactado', $vendedor, 'Fabricación de autopartes', 'Manufactura', 251, 'Rincón de Romos', 2890000, 0, 25, 'prospeccion', 'Mandar ficha técnica del robot', 0, 3],
            ['Estampados Pabellón', 'Ing. Lucía Esparza', 'Contactado', $laura, 'Estampado de lámina', 'Manufactura', 101, 'Pabellón de Arteaga', 6650000, 0, 30, 'referido', 'Llamar para agendar visita', -2, 6],
            ['Transportes de Carga Ojocaliente', 'Sr. Hugo Lozano', 'Contactado', $vendedor, 'Autotransporte de carga', 'Transporte', 51, 'Aguascalientes', 640000, 0, 20, 'redes', 'Enviar cotización del compresor', 1, 2],
            ['Maquinados de Precisión Encarnación', 'Ing. Fabiola Muñoz', 'Diagnóstico', $laura, 'Maquinado de piezas', 'Manufactura', 51, 'Encarnación de Díaz', 1850000, 0, 40, 'referido', 'Visita a planta', 0, 4],
            ['Fundidora de Aluminio del Centro', 'Ing. Germán Tovar', 'Diagnóstico', $owner, 'Fundición de aluminio', 'Manufactura', 251, 'San Francisco de los Romo', 8400000, 0, 45, 'prospeccion', 'Revisar capacidad de la celda', 2, 1],
            ['Pinturas Industriales El Llano', 'Lic. Verónica Pedroza', 'Diagnóstico', $vendedor, 'Aplicación de pintura', 'Manufactura', 31, 'El Llano', 3250000, 0, 35, 'sitio_web', 'Medir el área de la cabina', 3, 5],
            ['Ensambles Eléctricos Asientos', 'Ing. Alberto Soto', 'Propuesta', $laura, 'Ensamble de arneses', 'Manufactura', 251, 'Asientos', 1156000, 18000, 55, 'cliente_actual', 'Dar seguimiento a la propuesta', 0, 2],
            ['Carrocerías y Remolques Tepezalá', 'Sr. Ernesto Campos', 'Propuesta', $owner, 'Fabricación de remolques', 'Manufactura', 101, 'Tepezalá', 3850000, 0, 60, 'referido', 'Resolver dudas de la garantía', -1, 3],
            ['Metalmecánica del Valle', 'Ing. Claudia Reyes', 'Propuesta', $vendedor, 'Metalmecánica', 'Manufactura', 51, 'Aguascalientes', 2100000, 0, 50, 'prospeccion', 'Enviar propuesta corregida', 1, 1],
            ['Componentes de Frenos Cosío', 'Ing. Ramiro Durán', 'Negociación', $laura, 'Fabricación de frenos', 'Manufactura', 251, 'Cosío', 5360000, 25000, 75, 'referido', 'Cerrar condiciones de pago', 0, 1],
            ['Prensas y Partes San José', 'Lic. Gabriela Nava', 'Negociación', $owner, 'Estampado de autopartes', 'Manufactura', 101, 'San José de Gracia', 4300000, 0, 80, 'cliente_actual', 'Firmar el pedido', 2, 1],
            ['Componentes Metálicos TIER-1', 'Ing. Susana Prado', 'Ganado', $laura, 'Componentes metálicos', 'Manufactura', 251, 'San Luis Potosí', 2227200, 0, 100, 'referido', null, null, 20],
            ['Autopartes y Estampados del Centro', 'Ing. Javier Medina', 'Ganado', $owner, 'Estampado de autopartes', 'Manufactura', 101, 'Celaya', 4292000, 35000, 100, 'prospeccion', null, null, 35],
            ['Ensambladora Automotriz Aguascalientes', 'Lic. Rocío Olvera', 'Ganado', $laura, 'Ensamble automotriz', 'Manufactura', 251, 'Aguascalientes', 3352400, 0, 100, 'sitio_web', null, null, 48],
            ['Refacciones Industriales Tepetate', 'Sr. Iván Cortés', 'Perdido', $vendedor, 'Comercio de refacciones', 'Comercio al por mayor', 11, 'Aguascalientes', 795000, 0, 0, 'prospeccion', null, null, 15],
            ['Rectificadora La Estación', 'Sr. Mario Tiscareño', 'Perdido', $laura, 'Rectificado de motores', 'Talleres y reparación', 6, 'Aguascalientes', 1680000, 0, 0, 'redes', null, null, 28],
        ];

        $clientesGanados = ['Componentes Metálicos TIER-1' => $clients[2], 'Autopartes y Estampados del Centro' => $clients[1], 'Ensambladora Automotriz Aguascalientes' => $clients[0]];
        $leads = [];
        foreach ($rows as $i => [$empresa, $contacto, $etapa, $resp, $giro, $sector, $personal, $municipio, $valor, $mensual, $prob, $origen, $accion, $enDias, $sinContacto]) {
            $slug = Str::slug($empresa);
            $stage = $etapas[$etapa];
            $lead = Lead::withTrashed()->updateOrCreate(
                ['organization_id' => $org->id, 'empresa' => $empresa],
                [
                    'stage_id' => $stage->id,
                    'owner_id' => $resp?->id,
                    'client_id' => isset($clientesGanados[$empresa]) ? $clientesGanados[$empresa]->id : null,
                    'nombre' => $contacto,
                    'email' => "compras.{$slug}@example.com",
                    'sitio_web' => "https://www.example.com/{$slug}",
                    'telefono' => '449555'.sprintf('%04d', 100 + $i),
                    'origen' => $origen,
                    'giro' => $giro,
                    'sector' => $sector,
                    'personal_min' => $personal,
                    'municipio' => $municipio,
                    'valor_estimado' => $valor,
                    'valor_mensual' => $mensual,
                    'probabilidad' => $prob,
                    'proxima_accion' => $accion,
                    'proxima_accion_at' => $accion ? now()->addDays($enDias)->setTime(mt_rand(9, 17), 0) : null,
                    'ultimo_contacto_at' => $sinContacto ? now()->subDays($sinContacto) : null,
                    'cerrado_at' => $stage->esAbierta() ? null : now()->subDays($sinContacto),
                    'motivo_perdida' => $stage->es_perdida ? ($i % 2 ? 'Eligió otro proveedor' : 'Sin presupuesto este año') : null,
                    'orden' => $i,
                ]
            );
            $leads[$empresa] = $lead;

            $this->seedBitacora($lead, $resp ?? $owner, $sinContacto, $accion, $enDias, $i);
        }

        $this->seedCorreos($org, $owner, $leads);

        // Papelera: descartados que todavía se pueden restaurar.
        foreach ([['Herrería Don Chema', 'no_es_perfil'], ['Taller Mecánico Los Arellano', 'datos_malos']] as $i => [$empresa, $motivo]) {
            $descartado = Lead::withTrashed()->updateOrCreate(
                ['organization_id' => $org->id, 'empresa' => $empresa],
                [
                    'stage_id' => $etapas['Nuevo']->id,
                    'nombre' => $empresa,
                    'email' => 'contacto.'.Str::slug($empresa).'@example.com',
                    'origen' => 'prospeccion',
                    'sector' => 'Talleres y reparación',
                    'personal_min' => 0,
                    'municipio' => 'Aguascalientes',
                    'motivo_descarte' => $motivo,
                ]
            );
            $descartado->forceFill(['deleted_at' => now()->subDays(4 + $i)])->save();
        }
    }

    /** Lo que se ve en la ficha del prospecto y, si toca hoy, en sus pendientes. */
    private function seedBitacora(Lead $lead, User $user, ?int $sinContacto, ?string $accion, ?int $enDias, int $i): void
    {
        $registrar = function (string $tipo, string $descripcion, int $hace, array $extra = []) use ($lead, $user) {
            $actividad = CrmActivity::create($extra + [
                'organization_id' => $lead->organization_id,
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'completada_at' => now()->subDays($hace),
            ]);
            $actividad->forceFill(['created_at' => now()->subDays($hace)])->save();
        };

        if ($sinContacto) {
            $registrar('llamada', 'Se presentó la línea de equipos; pidió información por correo.', $sinContacto + 12);
            $registrar('email', 'Se envió catálogo y lista de precios.', $sinContacto + 7);
            $registrar(['whatsapp', 'visita', 'llamada'][$i % 3], [
                'Confirmó que están evaluando reemplazar equipo este trimestre.',
                'Recorrido por la planta: dos líneas pueden automatizarse.',
                'Revisamos alcance y tiempos de entrega con el área de compras.',
            ][$i % 3], $sinContacto);
        }
        if ($accion && $enDias <= 0) {
            CrmActivity::create([
                'organization_id' => $lead->organization_id,
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'tipo' => 'llamada',
                'descripcion' => $accion,
                'programada_at' => now()->addDays($enDias)->setTime(10 + $i % 7, 30),
            ]);
        }
    }

    /** Bandeja de borradores, secuencia ya enviada y dos respuestas para la campana. */
    private function seedCorreos(Organization $org, User $owner, array $leads): void
    {
        $crear = fn (string $empresa, string $asunto, string $cuerpo, ?int $hace = null, int $toque = 1) => CrmBorrador::create([
            'organization_id' => $org->id,
            'lead_id' => $leads[$empresa]->id,
            'user_id' => $owner->id,
            'asunto' => $asunto,
            'cuerpo' => $cuerpo,
            'toque' => $toque,
            'message_id' => $hace !== null ? Str::uuid().'@example.com' : null,
            'enviado_at' => $hace !== null ? now()->subDays($hace) : null,
        ]);
        // Que la campana de respuestas vuelva a encenderse aunque ya se hayan visto.
        $owner->forceFill(['respuestas_vistas_at' => null])->save();
        $cuerpo = fn (string $contacto) => "Hola {$contacto}:\n\nSomos Maquinaria Automotriz del Bajío. Ayudamos a plantas como la suya a automatizar soldadura, estampado y manejo de materiales, con instalación y servicio en la región.\n\n¿Le parece si platicamos 15 minutos esta semana?\n\nSaludos.";

        $crear('Troqueles Finos de Aguascalientes', 'Automatización para su línea de troquelado', $cuerpo('Ing. Delgado'));
        $crear('Recubrimientos Metálicos de Jesús María', 'Cabinas de pintura y hornos de curado', $cuerpo('Ing. Luna'));
        $crear('Moldes y Herramentales Calvillo', 'Centros de maquinado para herramental', $cuerpo('Sra. Rangel'));
        $crear('Autopartes Rincón de Romos', 'Automatización para su línea de autopartes', $cuerpo('Ing. Macías'), 6);
        $crear('Autopartes Rincón de Romos', 'Re: Automatización para su línea de autopartes', 'Le comparto dos casos de plantas similares, por si le sirven para evaluar.', 3, 2);
        $crear('Metalmecánica del Valle', 'Torno CNC para su producción de ejes', $cuerpo('Ing. Reyes'), 4);

        foreach ([
            ['Autopartes Rincón de Romos', 'Respuesta: Sí nos interesa. ¿Pueden mandarnos la ficha técnica del robot de 210 kg?', 1],
            ['Metalmecánica del Valle', 'Respuesta: Gracias, ¿qué tiempo de entrega manejan para el torno?', 0],
        ] as [$empresa, $texto, $hace]) {
            $actividad = CrmActivity::create([
                'organization_id' => $org->id,
                'lead_id' => $leads[$empresa]->id,
                'tipo' => 'email',
                'descripcion' => $texto,
                'mensaje_id' => Str::uuid().'@example.com',
                'completada_at' => now()->subDays($hace),
            ]);
            $actividad->forceFill(['created_at' => now()->subDays($hace)])->save();
        }
    }

    /**
     * Facturas desde enero del año pasado, declaraciones calculadas con las mismas
     * reglas de la pantalla y solicitudes de descarga del SAT.
     */
    private function seedContabilidad(User $owner, array $clients): void
    {
        $emisor = ['MAB150310XY1', 'Maquinaria Automotriz del Bajío S.A. de C.V.'];
        // [nombre, rfc, uso CFDI, mínimo, máximo, cada cuántos meses]
        $gastos = [
            ['Robótica Industrial de México S.A. de C.V.', 'RIM120534AB2', 'G01', 45000, 70000, 1],
            ['Consumibles de Soldadura del Centro S.A. de C.V.', 'CSC140605LM8', 'G01', 12000, 22000, 1],
            ['Energía Industrial del Bajío S.A. de C.V.', 'EIB100215TR7', 'G03', 15000, 24000, 1],
            ['Naves Industriales Aguascalientes S.A.', 'NIA090811KP3', 'G03', 28000, 28000, 1],
            ['Consultorio Médico Santa Elena', 'CMS880412HJ1', 'D01', 1800, 3500, 3],
            ['Comercializadora de Regalos Bajío', 'CRB970623PL5', 'S01', 900, 2500, 4],
        ];

        $totales = [];
        $mes = now()->subYear()->startOfYear();
        while ($mes->lte(now())) {
            $clave = "{$mes->year}-{$mes->month}";
            $totales[$clave] = array_fill_keys(['ingresos', 'gastos', 'iva_trasladado', 'iva_retenido', 'iva_acreditable', 'isr_retenido'], 0.0);
            $n = 0;
            $esteMes = $mes->isSameMonth(now());

            foreach (range(1, $esteMes ? 2 : mt_rand(4, 6)) as $_) {
                $cliente = $clients[array_rand($clients)];
                $subtotal = mt_rand(2500000, 6000000) / 100;
                $this->factura($owner, $mes, ++$n, 'emitida', 'I', $emisor, [$cliente->rfc, $cliente->razon_social], $subtotal, 'G03');
                $totales[$clave]['ingresos'] += $subtotal;
                $totales[$clave]['iva_trasladado'] += round($subtotal * 0.16, 2);
            }

            foreach ($gastos as [$nombre, $rfc, $uso, $min, $max, $cada]) {
                if ($mes->month % $cada || ($esteMes && $n > 4)) {
                    continue;
                }
                $subtotal = mt_rand($min * 100, $max * 100) / 100;
                $this->factura($owner, $mes, ++$n, 'recibida', 'I', [$rfc, $nombre], $emisor, $subtotal, $uso);
                if (! Factura::noDeducibleEnElMes($uso)) {
                    $totales[$clave]['gastos'] += $subtotal;
                    $totales[$clave]['iva_acreditable'] += round($subtotal * 0.16, 2);
                }
            }

            if ($mes->month % 5 === 0) {
                $cliente = $clients[array_rand($clients)];
                $subtotal = mt_rand(300000, 800000) / 100;
                $this->factura($owner, $mes, ++$n, 'emitida', 'E', $emisor, [$cliente->rfc, $cliente->razon_social], $subtotal, 'G02');
                $totales[$clave]['ingresos'] -= $subtotal;
                $totales[$clave]['iva_trasladado'] -= round($subtotal * 0.16, 2);
            }
            if ($mes->month % 2 === 0) {
                $cliente = $clients[array_rand($clients)];
                $this->factura($owner, $mes, ++$n, 'emitida', 'P', $emisor, [$cliente->rfc, $cliente->razon_social], 0, 'CP01');
            }

            $mes->addMonth();
        }

        $this->seedDeclaraciones($owner, $totales);
        $this->seedSat($owner);
    }

    private function factura(User $owner, $mes, int $n, string $tipo, string $comprobante, array $emisor, array $receptor, float $subtotal, string $uso): void
    {
        $hash = md5("demo-{$mes->format('Y-m')}-{$n}");
        $iva = round($subtotal * 0.16, 2);
        $fecha = $mes->copy()->day(min(1 + $n * 2, $mes->isSameMonth(now()) ? now()->day : 28));

        Factura::updateOrCreate(
            ['uuid' => strtoupper(vsprintf('%s-%s-%s-%s-%s', [substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 12, 4), substr($hash, 16, 4), substr($hash, 20, 12)]))],
            [
                'user_id' => $owner->id,
                'tipo_factura' => $tipo,
                'tipo_comprobante' => $comprobante,
                'fecha_emision' => $fecha,
                'año' => $mes->year,
                'mes' => $mes->month,
                'rfc_emisor' => $emisor[0],
                'nombre_emisor' => $emisor[1],
                'rfc_receptor' => $receptor[0],
                'nombre_receptor' => $receptor[1],
                'subtotal' => $subtotal,
                'descuento' => 0,
                'iva_trasladado' => $iva,
                'iva_retenido' => 0,
                'isr_retenido' => 0,
                'total' => $subtotal + $iva,
                'moneda' => 'MXN',
                'tipo_cambio' => 1,
                'metodo_pago' => $comprobante === 'P' ? 'PPD' : ($n % 3 ? 'PUE' : 'PPD'),
                'forma_pago' => $n % 3 ? '03' : '99',
                'uso_cfdi' => $uso,
                'regimen_fiscal_emisor' => '601',
                'es_deducible' => $tipo === 'recibida' && ! Factura::noDeducibleEnElMes($uso),
            ]
        );
    }

    /** Todo presentado y pagado, menos el penúltimo mes (por pagar) y el último (por presentar). */
    private function seedDeclaraciones(User $owner, array $totales): void
    {
        $ultimo = now()->subMonth();
        $hojas = DeclaracionSat::hojas($totales, [], now()->subYear()->year, $ultimo->year, $ultimo->month);
        $porPagar = $ultimo->copy()->subMonth();

        foreach ($hojas as $h) {
            if ($h['año'] === $ultimo->year && $h['mes'] === $ultimo->month) {
                Declaracion::where(['user_id' => $owner->id, 'año' => $h['año'], 'mes' => $h['mes']])->delete();

                continue;
            }

            $presentada = now()->setDate($h['año'], $h['mes'], 17)->addMonth();
            $sinPagar = $h['año'] === $porPagar->year && $h['mes'] === $porPagar->month;
            Declaracion::updateOrCreate(
                ['user_id' => $owner->id, 'año' => $h['año'], 'mes' => $h['mes']],
                [
                    'isr_pagado' => $h['isr_cargo'],
                    'iva_pagado' => $h['iva_cargo'],
                    'monto_linea_captura' => $h['isr_cargo'] + $h['iva_cargo'],
                    'fecha_presentacion' => $presentada,
                    'fecha_pago' => $sinPagar ? null : $presentada,
                    'vence_pago' => $sinPagar ? now()->addDays(10) : null,
                ]
            );
        }
    }

    /** Historial de descargas; la e.firma no se siembra porque sin archivos reales "Solicitar" truena. */
    private function seedSat(User $owner): void
    {
        foreach ([
            ['DEMO-0001', 'emitida', now()->subYear()->startOfYear(), now()->subYear()->endOfYear(), 68],
            ['DEMO-0002', 'recibida', now()->subYear()->startOfYear(), now()->subYear()->endOfYear(), 61],
            ['DEMO-0003', 'emitida', now()->startOfYear(), now()->subMonth()->endOfMonth(), 52],
            ['DEMO-0004', 'recibida', now()->startOfYear(), now()->subMonth()->endOfMonth(), 44],
        ] as [$id, $tipo, $inicio, $fin, $cfdis]) {
            SatSolicitud::updateOrCreate(['id_solicitud' => $id, 'user_id' => $owner->id], [
                'tipo_factura' => $tipo,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado' => 'descargada',
                'total_cfdis' => $cfdis,
                'facturas_importadas' => $cfdis,
                'facturas_duplicadas' => 0,
            ]);
        }
    }
}
