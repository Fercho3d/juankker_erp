<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El SKU y el código de barras eran únicos en toda la plataforma. Además de
 * delatar que otra empresa ya tenía un producto (el error de duplicado), impedía
 * que dos negocios dieran de alta el mismo EAN —que es universal: la misma
 * Coca-Cola trae el mismo código en todas las tiendas— o un producto "001".
 * Pasan a ser únicos por organización.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->update(['product_variants.organization_id' => DB::raw('products.organization_id')]);

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_sku_unique');
            $table->dropUnique('product_variants_codigo_barras_unique');
            $table->unique(['organization_id', 'sku']);
            $table->unique(['organization_id', 'codigo_barras']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'sku']);
            $table->dropUnique(['organization_id', 'codigo_barras']);
            $table->unique('sku');
            $table->unique('codigo_barras');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
