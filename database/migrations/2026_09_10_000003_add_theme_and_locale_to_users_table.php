<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tema e idioma viajan con la cuenta. Null significa "sin elegir": se usa la
 * cookie del navegador y, sin ella, sistema y español.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->nullable()->after('login_count');
            $table->string('locale', 5)->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'locale']);
        });
    }
};
