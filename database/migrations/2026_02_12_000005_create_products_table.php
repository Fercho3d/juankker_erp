<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');

            $table->string('codigo', 100);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('unidad_medida', ['pieza', 'kg', 'litro', 'metro', 'caja', 'paquete', 'servicio'])->default('pieza');
            $table->enum('tipo_producto', ['simple', 'variable'])->default('simple');

            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_mayoreo', 10, 2)->nullable();

            $table->integer('stock_minimo')->default(0);
            $table->string('codigo_sat', 10)->nullable();
            $table->boolean('permite_decimales')->default(false);
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['organization_id', 'codigo']);
            $table->index(['organization_id', 'activo']);
            $table->index(['organization_id', 'category_id']);
            $table->index('codigo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
