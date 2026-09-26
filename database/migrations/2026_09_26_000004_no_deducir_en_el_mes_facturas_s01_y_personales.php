<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las facturas recibidas con uso S01 (sin efectos fiscales) o D (deducciones
 * personales de la anual) se habían importado como gasto deducible del mes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('facturas')
            ->where('tipo_factura', 'recibida')
            ->where(fn ($q) => $q->where('uso_cfdi', 'S01')->orWhere('uso_cfdi', 'like', 'D%'))
            ->update(['es_deducible' => false]);
    }
};
