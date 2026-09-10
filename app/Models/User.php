<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * Valores por omisión en memoria. La base ya pone activo = true, pero un
     * User::create() recién hecho no la relee: sin esto, `activo` valdría null
     * y el middleware lo tomaría por desactivado.
     */
    protected $attributes = [
        'activo' => true,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'is_superadmin',
        'last_login_at',
        'login_count',
        'theme',
        'locale',
        'role_id',
        'activo',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isSuperadmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_superadmin' => 'boolean',
        'password' => 'hashed',
        'theme' => \App\Support\Theme::class,
        'locale' => \App\Support\Locale::class,
        'activo' => 'boolean',
    ];

    /* -------------------- Perfil y accesos -------------------- */

    /** Pantalla de inicio de cada módulo, en el orden en que se prefiere caer. */
    private const INICIO_POR_MODULO = [
        'crm' => 'crm.pendientes',
        'pos' => 'pos.index',
        'ventas' => 'sales.index',
        'productos' => 'productos.index',
        'inventario' => 'inventario.index',
        'clientes' => 'clientes.index',
        'proveedores' => 'proveedores.index',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function esDueno(): bool
    {
        return $this->organization_id !== null && $this->organization?->owner_id === $this->id;
    }

    /**
     * Todo acceso: superadmin, dueño, perfil de administrador, o quien no tiene
     * perfil (los usuarios de antes de que existieran los perfiles).
     */
    public function esAdmin(): bool
    {
        return $this->isSuperadmin() || $this->esDueno() || $this->role_id === null || (bool) $this->role?->es_admin;
    }

    /** @return list<string> */
    public function modulos(): array
    {
        return $this->esAdmin() ? array_keys(Role::MODULOS) : ($this->role?->modulos() ?? []);
    }

    public function puede(string $modulo): bool
    {
        return in_array($modulo, $this->modulos(), true);
    }

    public function veSoloSusProspectos(): bool
    {
        return ! $this->esAdmin() && (bool) $this->role?->veSoloSusProspectos();
    }

    /** A dónde mandarlo al entrar: la primera sección a la que tiene acceso. */
    public function inicio(): string
    {
        foreach (self::INICIO_POR_MODULO as $modulo => $ruta) {
            if ($this->puede($modulo)) {
                return route($ruta);
            }
        }

        return route('profile.edit');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
