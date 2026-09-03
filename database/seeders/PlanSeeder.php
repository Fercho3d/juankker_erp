<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => 'gratis',
                'name' => 'Gratis',
                'tagline' => 'Lo básico para empezar a ordenar tu negocio.',
                'price' => 0,
                'precio_anual' => 0,
                'max_users' => 2,
                'max_branches' => 1,
                'max_products' => 50,
                'max_storage_gb' => 1,
                'modules' => ['clientes', 'productos', 'categorias', 'proveedores'],
                'features' => ['Clientes y proveedores', 'Catálogo de productos', 'Soporte por correo'],
                'orden' => 1, 'destacado' => false,
            ],
            [
                'key' => 'basico',
                'name' => 'Básico',
                'tagline' => 'Para pequeños negocios con punto de venta.',
                'price' => 499,
                'precio_anual' => 4990,
                'max_users' => 5,
                'max_branches' => 1,
                'max_products' => 1000,
                'max_storage_gb' => 5,
                'modules' => ['clientes', 'productos', 'categorias', 'proveedores', 'inventario', 'pos', 'ventas', 'compras'],
                'features' => ['Todo lo de Gratis', 'Punto de Venta (POS)', 'Inventario y almacén', 'Compras y proveedores'],
                'orden' => 2, 'destacado' => false,
            ],
            [
                'key' => 'profesional',
                'name' => 'Profesional',
                'tagline' => 'Para empresas en crecimiento y multisucursal.',
                'price' => 1299,
                'precio_anual' => 12990,
                'max_users' => 15,
                'max_branches' => 3,
                'max_products' => 5000,
                'max_storage_gb' => 25,
                'modules' => ['clientes', 'productos', 'categorias', 'proveedores', 'inventario', 'pos', 'ventas', 'compras', 'cfdi', 'finanzas', 'crm', 'reportes'],
                'features' => ['Todo lo de Básico', 'Facturación CFDI 4.0', 'Finanzas y contabilidad', 'CRM y reportes BI', 'Hasta 3 sucursales'],
                'orden' => 3, 'destacado' => true,
            ],
            [
                'key' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'Operación industrial a gran escala.',
                'price' => 3499,
                'precio_anual' => 34990,
                'max_users' => 100,
                'max_branches' => 20,
                'max_products' => 100000,
                'max_storage_gb' => 100,
                'modules' => ['clientes', 'productos', 'categorias', 'proveedores', 'inventario', 'pos', 'ventas', 'compras', 'cfdi', 'finanzas', 'crm', 'reportes', 'nomina', 'produccion', 'api'],
                'features' => ['Todo lo de Profesional', 'Nómina y RH (CFDI)', 'Producción y manufactura', 'API y multisucursal ilimitada', 'Soporte prioritario'],
                'orden' => 4, 'destacado' => false,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['key' => $data['key']], $data + ['activo' => true, 'visible' => true]);
        }
    }
}
