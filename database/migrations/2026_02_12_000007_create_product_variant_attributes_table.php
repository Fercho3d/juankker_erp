<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_attribute_value_id'], 'variant_attribute_unique');
            $table->index('product_variant_id');
            $table->index('product_attribute_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};
