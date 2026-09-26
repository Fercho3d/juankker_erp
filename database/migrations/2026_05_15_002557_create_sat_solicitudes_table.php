<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('id_solicitud')->nullable();
            $table->enum('tipo_factura', ['emitida', 'recibida']);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['pendiente', 'en_proceso', 'lista', 'descargada', 'error', 'rechazada'])
                ->default('pendiente');
            $table->unsignedInteger('total_cfdis')->default(0);
            $table->json('paquetes_ids')->nullable();
            $table->unsignedInteger('facturas_importadas')->default(0);
            $table->unsignedInteger('facturas_duplicadas')->default(0);
            $table->text('mensaje_error')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_solicitudes');
    }
};
