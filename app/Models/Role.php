<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Perfil de acceso dentro de una organización ("Vendedor", "Almacén"…): a qué
 * módulos entra y, en el CRM, si ve todo el embudo o sólo sus prospectos.
 */
class Role extends Model
{
    protected $fillable = ['organization_id', 'nombre', 'permisos', 'crm_alcance', 'es_admin'];

    protected $casts = [
        'permisos' => 'array',
        'es_admin' => 'boolean',
    ];

    /** Módulos que se pueden dar o quitar, en el orden en que se muestran. */
    public const MODULOS = [
        'crm' => 'CRM: pendientes y embudo',
        'pos' => 'Punto de venta',
        'ventas' => 'Historial de ventas',
        'productos' => 'Productos, categorías, marcas y atributos',
        'inventario' => 'Inventario',
        'clientes' => 'Clientes',
        'proveedores' => 'Proveedores',
        'equipo' => 'Equipo y perfiles',
        'plan' => 'Plan y facturación',
    ];

    public const ALCANCE_TODOS = 'todos';
    public const ALCANCE_PROPIOS = 'propios';

    /** Perfiles con los que arranca cada organización; se editan a gusto. */
    public const PREDETERMINADOS = [
        ['nombre' => 'Administrador', 'es_admin' => true, 'crm_alcance' => 'todos', 'permisos' => []],
        ['nombre' => 'Gerente de ventas', 'es_admin' => false, 'crm_alcance' => 'todos', 'permisos' => ['crm', 'clientes', 'ventas']],
        ['nombre' => 'Vendedor', 'es_admin' => false, 'crm_alcance' => 'propios', 'permisos' => ['crm', 'clientes']],
        ['nombre' => 'Punto de venta', 'es_admin' => false, 'crm_alcance' => 'todos', 'permisos' => ['pos', 'ventas', 'clientes']],
        ['nombre' => 'Almacén', 'es_admin' => false, 'crm_alcance' => 'todos', 'permisos' => ['productos', 'inventario', 'proveedores']],
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeDeOrganizacion($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /** Los perfiles de la organización; la primera vez siembra los predeterminados. */
    public static function paraOrganizacion(int $organizationId): Collection
    {
        if (! static::deOrganizacion($organizationId)->exists()) {
            foreach (self::PREDETERMINADOS as $perfil) {
                static::create($perfil + ['organization_id' => $organizationId]);
            }
        }

        return static::deOrganizacion($organizationId)->orderByDesc('es_admin')->orderBy('nombre')->get();
    }

    /** @return list<string> */
    public function modulos(): array
    {
        return $this->es_admin ? array_keys(self::MODULOS) : array_values(array_intersect(array_keys(self::MODULOS), $this->permisos ?? []));
    }

    public function veSoloSusProspectos(): bool
    {
        return ! $this->es_admin && $this->crm_alcance === self::ALCANCE_PROPIOS;
    }
}
