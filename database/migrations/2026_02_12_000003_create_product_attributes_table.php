<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 100);
            $table->enum('tipo', ['select', 'color', 'text'])->default('select');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'nombre']);
            $table->index(['organization_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
