<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    protected $fillable = [
        'uuid', 'tipo_factura', 'tipo_comprobante', 'fecha_emision',
        'año', 'mes',
        'rfc_emisor', 'nombre_emisor', 'rfc_receptor', 'nombre_receptor',
        'subtotal', 'descuento', 'iva_trasladado', 'iva_retenido', 'isr_retenido', 'total',
        'moneda', 'tipo_cambio', 'metodo_pago', 'forma_pago', 'uso_cfdi', 'regimen_fiscal_emisor',
        'es_deducible', 'notas', 'xml_path', 'pdf_path', 'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'iva_trasladado' => 'decimal:2',
        'iva_retenido' => 'decimal:2',
        'isr_retenido' => 'decimal:2',
        'total' => 'decimal:2',
        'tipo_cambio' => 'decimal:6',
        'es_deducible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Tasas ISR RESICO PF 2024 (mensuales)
    public static function tasaResicoMensual(float $ingresos): float
    {
        return match (true) {
            $ingresos <= 25000 => 0.01,
            $ingresos <= 50000 => 0.011,
            $ingresos <= 83333.33 => 0.015,
            $ingresos <= 208333.33 => 0.02,
            default => 0.025,
        };
    }

    // Nombres de meses en español
    public static function nombreMes(int $mes): string
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ][$mes] ?? '';
    }
}
