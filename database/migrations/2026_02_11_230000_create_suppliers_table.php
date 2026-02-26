<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');

            // Datos fiscales
            $table->enum('tipo_persona', ['Física', 'Moral']);
            $table->string('razon_social');
            $table->string('rfc', 13);
            $table->string('curp', 18)->nullable();
            $table->string('regimen_fiscal');
            $table->string('uso_cfdi');

            // Contacto
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('contacto_nombre')->nullable();

            // Dirección
            $table->string('calle');
            $table->string('num_exterior');
            $table->string('num_interior')->nullable();
            $table->string('colonia');
            $table->string('codigo_postal', 5);
            $table->string('ciudad');
            $table->string('estado');

            // Otros
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['organization_id', 'rfc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
