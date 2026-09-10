<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');

            $table->string('nombre');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('color', 20)->default('slate');
            $table->boolean('es_ganada')->default(false);
            $table->boolean('es_perdida')->default(false);

            $table->timestamps();

            $table->index(['organization_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_stages');
    }
};
