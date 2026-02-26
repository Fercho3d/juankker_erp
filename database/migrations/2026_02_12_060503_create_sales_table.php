<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->string('folio')->nullable();
            $table->enum('estatus', ['pendiente', 'completada', 'cancelada'])->default('pendiente');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('metodo_pago')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            // Unique folio per organization
            $table->unique(['organization_id', 'folio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
