<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // gratis, basico, profesional, enterprise
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->decimal('price', 10, 2)->default(0);        // MXN / mes
            $table->decimal('precio_anual', 10, 2)->nullable(); // MXN / año
            $table->unsignedInteger('max_users')->default(2);
            $table->unsignedInteger('max_branches')->default(1);
            $table->unsignedInteger('max_products')->default(50);
            $table->unsignedInteger('max_storage_gb')->default(1);
            $table->json('modules')->nullable();   // ['finanzas','cfdi','inventario','pos',...]
            $table->json('features')->nullable();  // bullets para la página de precios
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
