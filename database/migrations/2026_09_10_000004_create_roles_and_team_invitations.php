<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfiles de acceso por organización y las invitaciones para sumar gente.
 *
 * Quien no tiene perfil conserva el acceso completo, como antes de que
 * existieran: así nadie pierde nada al desplegar. Todo el que entra por
 * invitación llega con un perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->json('permisos');                       // claves de Role::MODULOS
            $table->string('crm_alcance', 10)->default('todos'); // todos | propios
            $table->boolean('es_admin')->default(false);    // el Administrador: todo, no se edita
            $table->timestamps();

            $table->unique(['organization_id', 'nombre']);
        });

        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->boolean('activo')->default(true)->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('activo');
        });
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('roles');
    }
};
