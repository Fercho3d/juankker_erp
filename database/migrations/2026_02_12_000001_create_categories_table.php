<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'nombre']);
            $table->index(['organization_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
