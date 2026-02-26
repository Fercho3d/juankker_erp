<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku', 100)->unique();
            $table->string('codigo_barras', 100)->nullable()->unique();
            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_mayoreo', 10, 2)->nullable();
            $table->integer('stock_actual')->default(0);
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'activo']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
