<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Hasta cuándo vio el usuario las respuestas de prospectos: lo de después es "nuevo". */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('respuestas_vistas_at')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('respuestas_vistas_at');
        });
    }
};
