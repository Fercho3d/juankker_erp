<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->date('vence_pago')->nullable()->after('fecha_pago');
        });
    }

    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn('vence_pago');
        });
    }
};
