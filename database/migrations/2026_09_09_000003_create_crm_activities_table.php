<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('tipo', ['llamada', 'whatsapp', 'email', 'visita', 'nota', 'etapa']);
            $table->text('descripcion')->nullable();
            $table->dateTime('programada_at')->nullable();
            $table->dateTime('completada_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'programada_at']);
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
