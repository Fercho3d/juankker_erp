<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('stage_id')->constrained('crm_stages')->onDelete('cascade');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();

            // Contacto
            $table->string('nombre');
            $table->string('empresa')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();

            // Oportunidad
            $table->string('origen')->default('prospeccion');
            $table->decimal('valor_estimado', 12, 2)->default(0);
            $table->decimal('valor_mensual', 12, 2)->default(0);
            $table->unsignedTinyInteger('probabilidad')->default(20);

            // Seguimiento
            $table->string('proxima_accion')->nullable();
            $table->dateTime('proxima_accion_at')->nullable();
            $table->dateTime('ultimo_contacto_at')->nullable();

            // Cierre
            $table->dateTime('cerrado_at')->nullable();
            $table->string('motivo_perdida')->nullable();

            $table->text('notas')->nullable();
            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();

            $table->index(['organization_id', 'stage_id', 'orden']);
            $table->index(['organization_id', 'proxima_accion_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
