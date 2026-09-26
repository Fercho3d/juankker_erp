<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatSolicitud extends Model
{
    protected $table = 'sat_solicitudes';

    protected $fillable = [
        'id_solicitud', 'tipo_factura', 'fecha_inicio', 'fecha_fin',
        'estado', 'total_cfdis', 'paquetes_ids',
        'facturas_importadas', 'facturas_duplicadas',
        'mensaje_error', 'user_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'paquetes_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En proceso',
            'lista' => 'Lista para descargar',
            'descargada' => 'Descargada',
            'error' => 'Error',
            'rechazada' => 'Rechazada por SAT',
            default => $this->estado,
        };
    }

    public function colorEstado(): string
    {
        return match ($this->estado) {
            'pendiente' => '#d97706',
            'en_proceso' => '#2563eb',
            'lista' => '#16a34a',
            'descargada' => '#6b7280',
            'error' => '#dc2626',
            'rechazada' => '#dc2626',
            default => '#374151',
        };
    }
}
