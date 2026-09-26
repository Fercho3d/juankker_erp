<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->enum('tipo_factura', ['emitida', 'recibida']);
            $table->enum('tipo_comprobante', ['I', 'E', 'T', 'N', 'P'])->default('I');
            $table->date('fecha_emision');
            $table->unsignedSmallInteger('año');
            $table->unsignedTinyInteger('mes');

            $table->string('rfc_emisor', 13);
            $table->string('nombre_emisor');
            $table->string('rfc_receptor', 13);
            $table->string('nombre_receptor');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('iva_trasladado', 14, 2)->default(0);
            $table->decimal('iva_retenido', 14, 2)->default(0);
            $table->decimal('isr_retenido', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->string('moneda', 3)->default('MXN');
            $table->decimal('tipo_cambio', 10, 6)->default(1);
            $table->string('metodo_pago', 3)->nullable();
            $table->string('forma_pago', 2)->nullable();
            $table->string('uso_cfdi', 10)->nullable();
            $table->string('regimen_fiscal_emisor', 4)->nullable();

            $table->boolean('es_deducible')->default(true);
            $table->string('notas')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['año', 'mes']);
            $table->index(['tipo_factura', 'año']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
