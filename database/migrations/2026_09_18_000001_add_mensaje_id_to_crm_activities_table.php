<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Message-ID del correo que originó el movimiento, para no registrarlo dos veces. */
    public function up(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->string('mensaje_id')->nullable()->after('descripcion');
            $table->index(['organization_id', 'mensaje_id']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'mensaje_id']);
            $table->dropColumn('mensaje_id');
        });
    }
};
