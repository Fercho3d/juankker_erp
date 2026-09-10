<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'stage_id', 'owner_id', 'client_id',
        'nombre', 'empresa', 'email', 'telefono',
        'origen', 'valor_estimado', 'valor_mensual', 'probabilidad',
        'proxima_accion', 'proxima_accion_at', 'ultimo_contacto_at',
        'cerrado_at', 'motivo_perdida', 'notas', 'orden',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'valor_mensual' => 'decimal:2',
        'probabilidad' => 'integer',
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
                    ->orWhere('telefono', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * Leads cuya próxima acción ya venció o vence hoy.
     */
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
}
