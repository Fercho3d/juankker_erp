<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->decimal('monto_linea_captura', 12, 2)->nullable()->after('isr_pagado');
        });
    }

    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn('monto_linea_captura');
        });
    }
};
