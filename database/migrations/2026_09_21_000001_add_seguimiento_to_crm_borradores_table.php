<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_borradores', function (Blueprint $table) {
            // 1 = primer correo, 2 y 3 = seguimientos de la secuencia.
            $table->unsignedTinyInteger('toque')->default(1)->after('cuerpo');
            // Message-ID con el que salió, y el del correo al que contesta (mismo hilo).
            $table->string('message_id')->nullable()->after('toque');
            $table->string('responde_a')->nullable()->after('message_id');

            $table->index(['lead_id', 'toque']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_borradores', function (Blueprint $table) {
            $table->dropIndex(['lead_id', 'toque']);
            $table->dropColumn(['toque', 'message_id', 'responde_a']);
        });
    }
};
