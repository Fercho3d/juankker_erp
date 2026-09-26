<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('declaraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('año');
            $table->unsignedTinyInteger('mes')->nullable(); // null = anual
            $table->decimal('iva_pagado', 12, 2)->default(0);
            $table->decimal('isr_pagado', 12, 2)->default(0);
            $table->date('fecha_presentacion')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->string('notas', 300)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'año', 'mes']);
            $table->index(['user_id', 'año']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaraciones');
    }
};
