<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El lector de XML sumaba los impuestos de cada concepto y además los del
 * comprobante, así que el IVA y las retenciones quedaron al doble. Se detectan
 * porque sólo a la mitad cuadran con total - subtotal + descuento. Reaplicarla
 * no hace nada: ya corregidas, cuadran completas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('facturas')
            ->where('tipo_comprobante', '!=', 'P')
            ->where(fn ($q) => $q->where('iva_trasladado', '>', 0)->orWhere('iva_retenido', '>', 0)->orWhere('isr_retenido', '>', 0))
            ->whereRaw('ABS((iva_trasladado - iva_retenido - isr_retenido) - (total - subtotal + descuento)) >= 0.05')
            ->whereRaw('ABS((iva_trasladado - iva_retenido - isr_retenido) / 2 - (total - subtotal + descuento)) < 0.05')
            ->update([
                'iva_trasladado' => DB::raw('ROUND(iva_trasladado / 2, 2)'),
                'iva_retenido' => DB::raw('ROUND(iva_retenido / 2, 2)'),
                'isr_retenido' => DB::raw('ROUND(isr_retenido / 2, 2)'),
            ]);
    }
};
