<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'lead_id', 'user_id',
        'tipo', 'descripcion', 'programada_at', 'completada_at',
    ];

    protected $casts = [
        'programada_at' => 'datetime',
        'completada_at' => 'datetime',
    ];

    public const TIPOS = [
        'llamada' => 'Llamada',
        'whatsapp' => 'WhatsApp',
        'email' => 'Correo',
        'visita' => 'Visita',
        'nota' => 'Nota',
        'etapa' => 'Cambio de etapa',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDeOrganizacion($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopePendientes($query)
    {
        return $query->whereNull('completada_at')->whereNotNull('programada_at');
    }
}
