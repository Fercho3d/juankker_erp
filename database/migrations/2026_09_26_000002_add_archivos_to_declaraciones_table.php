<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->string('acuse_path')->nullable()->after('notas');
            $table->string('pago_path')->nullable()->after('acuse_path');
        });
    }

    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn(['acuse_path', 'pago_path']);
        });
    }
};
