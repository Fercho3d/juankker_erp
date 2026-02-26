<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tipo_persona',
        'razon_social',
        'rfc',
        'curp',
        'regimen_fiscal',
        'uso_cfdi',
        'email',
        'telefono',
        'calle',
        'num_exterior',
        'num_interior',
        'colonia',
        'codigo_postal',
        'ciudad',
        'estado',
        'notas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('razon_social', 'like', "%{$term}%")
                    ->orWhere('rfc', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }
        return $query;
    }

    public function scopeOfType($query, $tipo)
    {
        if ($tipo) {
            $query->where('tipo_persona', $tipo);
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('activo', $status);
        }
        return $query;
    }
}
