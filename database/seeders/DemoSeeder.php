<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cuenta de PRUEBA prellenada: proveedor de maquinaria industrial
 * enfocada a la industria automotriz (robots, prensas, soldadura,
 * fundición a presión, CNC, metrología, pintura, logística).
 *
 * Acceso: demo@juankker.com / Demo1234
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
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
        User::updateOrCreate(
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
        $this->seedSales($org, $owner, $clients, $products);

        $this->command?->info("Demo: {$org->name} · ".count($products).' máquinas · '.count($clients).' clientes.');
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
        foreach (['FANUC', 'KUKA', 'ABB', 'Yaskawa Motoman', 'Siemens', 'Bosch Rexroth', 'Haas', 'Mazak', 'Zeiss', 'Atlas Copco', 'Schuler', 'Dürr'] as $n) {
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
        ];

        $out = [];
        foreach ($data as [$rs, $rfc, $email, $ciudad, $estado, $cp]) {
            $out[] = Client::updateOrCreate(
                ['organization_id' => $org->id, 'rfc' => $rfc],
                [
                    'tipo_persona' => 'Moral', 'razon_social' => $rs,
                    'regimen_fiscal' => '601', 'uso_cfdi' => 'G03',
                    'email' => $email, 'telefono' => '4499876543',
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
                    'stock_minimo' => 1,
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

    private function seedSales(Organization $org, User $user, array $clients, array $products): void
    {
        // Un par de ventas para alimentar estadísticas de uso.
        $ventas = [
            [$products[0], 1, $clients[0]],   // Robot FANUC
            [$products[8], 2, $clients[1]],   // 2 CNC Haas
            [$products[16], 3, $clients[2]],  // 3 compresores
        ];

        $folio = 1000;
        foreach ($ventas as [$product, $cant, $client]) {
            $variant = $product->variants()->first();
            $importe = (float) $variant->precio_venta * $cant;
            $impuesto = round($importe * 0.16, 2);

            $sale = Sale::firstOrCreate(
                ['organization_id' => $org->id, 'folio' => 'V-'.(++$folio)],
                [
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'estatus' => 'completada',
                    'subtotal' => $importe,
                    'impuesto' => $impuesto,
                    'total' => $importe + $impuesto,
                    'metodo_pago' => 'transferencia',
                    'created_at' => now()->subDays(rand(2, 40)),
                ]
            );

            SaleItem::firstOrCreate(
                ['sale_id' => $sale->id, 'product_variant_id' => $variant->id],
                [
                    'cantidad' => $cant,
                    'precio_unitario' => $variant->precio_venta,
                    'importe' => $importe,
                    'producto_nombre_snapshot' => $product->nombre,
                ]
            );
        }
    }
}
