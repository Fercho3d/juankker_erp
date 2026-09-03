<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('organization_id');
            $table->timestamp('last_login_at')->nullable()->after('is_superadmin');
            $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_superadmin', 'last_login_at', 'login_count']);
        });
    }
};
