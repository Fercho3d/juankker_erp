<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('name')->constrained('users')->nullOnDelete();
            $table->string('plan')->default('gratis')->after('owner_id');
            $table->string('subscription_status')->default('trial')->after('plan'); // trial, active, expired, cancelled, free
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->unsignedInteger('max_users')->default(2)->after('subscription_ends_at');
            $table->unsignedInteger('max_branches')->default(1)->after('max_users');
            $table->unsignedInteger('max_products')->default(50)->after('max_branches');
            $table->unsignedInteger('max_storage_gb')->default(1)->after('max_products');
            $table->boolean('is_active')->default(true)->after('max_storage_gb');
            $table->string('environment')->default('produccion')->after('is_active'); // beta, produccion
            $table->string('stripe_id')->nullable()->after('environment');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn([
                'plan', 'subscription_status', 'trial_ends_at', 'subscription_ends_at',
                'max_users', 'max_branches', 'max_products', 'max_storage_gb',
                'is_active', 'environment', 'stripe_id',
            ]);
        });
    }
};
