<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Papelera de prospectos: descartar no es perder. "Perdido" es una etapa del
 * embudo (se trabajó y no se cerró); descartado es quien nunca fue prospecto,
 * y no debe contar en las métricas ni volver a entrar con una importación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('motivo_descarte', 40)->nullable()->after('motivo_perdida');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('motivo_descarte');
        });
    }
};
