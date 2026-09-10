<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'stage_id', 'owner_id', 'client_id',
        'nombre', 'empresa', 'email', 'telefono',
        'origen', 'giro', 'sector', 'personal_min', 'municipio',
        'valor_estimado', 'valor_mensual', 'probabilidad',
        'proxima_accion', 'proxima_accion_at', 'ultimo_contacto_at',
        'cerrado_at', 'motivo_perdida', 'motivo_descarte', 'notas', 'orden',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'valor_mensual' => 'decimal:2',
        'probabilidad' => 'integer',
        'personal_min' => 'integer',
        'proxima_accion_at' => 'datetime',
        'ultimo_contacto_at' => 'datetime',
        'cerrado_at' => 'datetime',
    ];

    public const ORIGENES = [
        'prospeccion' => 'Prospección en frío',
        'referido' => 'Referido',
        'sitio_web' => 'Sitio web',
        'redes' => 'Redes sociales',
        'cliente_actual' => 'Cliente actual',
        'otro' => 'Otro',
    ];

    public const MOTIVOS_DESCARTE = [
        'ya_tiene_sistema' => 'Ya tiene sistema',
        'no_es_perfil' => 'No es nuestro perfil',
        'datos_malos' => 'Cerró o los datos están mal',
        'no_contactar' => 'Pidió no ser contactado',
        'otro' => 'Otro',
    ];

    /**
     * Estratos de personal del DENUE, por su piso: el estrato "11 a 30 personas"
     * se guarda como personal_min = 11, así que cada rango es un valor exacto.
     */
    public const TAMANOS = [
        0 => '0 a 5',
        6 => '6 a 10',
        11 => '11 a 30',
        31 => '31 a 50',
        51 => '51 a 100',
        101 => '101 a 250',
        251 => '251 o más',
    ];

    /** Sector SCIAN (dos primeros dígitos) => nombre con el que se filtra. */
    public const SECTORES_SCIAN = [
        '23' => 'Construcción', '31' => 'Manufactura', '32' => 'Manufactura', '33' => 'Manufactura',
        '43' => 'Comercio al por mayor', '46' => 'Comercio al por menor',
        '48' => 'Transporte', '49' => 'Transporte',
        '72' => 'Restaurantes y hoteles', '81' => 'Talleres y reparación',
    ];

    public function stage()
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class)->latest('created_at');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /* -------------------- Scopes -------------------- */

    public function scopeDeOrganizacion($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeAbiertos($query)
    {
        return $query->whereHas('stage', function ($q) {
            $q->where('es_ganada', false)->where('es_perdida', false);
        });
    }

    public function scopeSearch($query, ?string $term)
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('empresa', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('telefono', 'like', "%{$term}%")
                    ->orWhere('giro', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * Leads cuya próxima acción ya venció o vence hoy.
     */
    /**
     * Filtros del embudo: sector, tamaño mínimo, municipio y canal de contacto.
     *
     * @param  array<string, mixed>  $f
     */
    public function scopeFiltrar($query, array $f)
    {
        return $query
            ->search($f['search'] ?? null)
            ->when($f['sector'] ?? null, fn ($q, $v) => $q->where('sector', $v))
            ->when(($f['tamano'] ?? '') !== '', fn ($q) => $q->where('personal_min', (int) $f['tamano']))
            ->when($f['municipio'] ?? null, fn ($q, $v) => $q->where('municipio', $v))
            ->when(($f['contacto'] ?? null) === 'telefono', fn ($q) => $q->whereNotNull('telefono'))
            ->when(($f['contacto'] ?? null) === 'email', fn ($q) => $q->whereNotNull('email'));
    }

    public function scopePendientes($query)
    {
        return $query->abiertos()
            ->whereNotNull('proxima_accion_at')
            ->where('proxima_accion_at', '<=', now()->endOfDay());
    }

    /* -------------------- Derivados -------------------- */

    public function valorPonderado(): float
    {
        return round((float) $this->valor_estimado * $this->probabilidad / 100, 2);
    }

    public function diasSinContacto(): ?int
    {
        $ref = $this->ultimo_contacto_at ?? $this->created_at;

        return $ref ? (int) $ref->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }

    public function estaVencido(): bool
    {
        return $this->proxima_accion_at !== null
            && $this->proxima_accion_at->isPast()
            && ! $this->proxima_accion_at->isToday();
    }

    /** "11 a 30", o null si el lead no viene del DENUE. */
    public function rangoPersonal(): ?string
    {
        return isset(self::TAMANOS[$this->personal_min]) ? __(self::TAMANOS[$this->personal_min]) : null;
    }

    public static function sectorDe(?string $scian, ?string $giro = null): ?string
    {
        if ($scian && isset(self::SECTORES_SCIAN[substr($scian, 0, 2)])) {
            return self::SECTORES_SCIAN[substr($scian, 0, 2)];
        }

        foreach (['Comercio al por menor', 'Comercio al por mayor'] as $prefijo) {
            if ($giro && str_starts_with($giro, $prefijo)) {
                return $prefijo;
            }
        }

        return $giro && preg_match('/^(Fabricación|Elaboración)/u', $giro) ? 'Manufactura'
            : ($giro && str_starts_with($giro, 'Reparación') ? 'Talleres y reparación' : null);
    }

    /**
     * Los diez dígitos nacionales, listos para tel: y wa.me. Tolera lo que se
     * capture a mano: espacios, guiones o el +52 por delante.
     */
    public function telefonoDigitos(): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $this->telefono);

        return strlen($digitos) >= 10 ? substr($digitos, -10) : null;
    }
}
