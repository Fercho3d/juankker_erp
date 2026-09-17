<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_borradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asunto');
            $table->text('cuerpo');
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'enviado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_borradores');
    }
};
