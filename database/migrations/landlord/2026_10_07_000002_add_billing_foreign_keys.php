<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

return new class extends Migration
{
    /**
     * Referential integrity for the billing chain — previously enforced only
     * at the application level (plan.delete_blocked, lifecycle delete order).
     * Delete semantics mirror the code: a removed plan must not strand a
     * tenant (null), subscriptions die with their tenant (cascade), and a
     * plan referenced by a subscription is not deletable (restrict).
     */
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->table('tenants', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });

        Schema::connection($connection)->table('subscriptions', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['plan_id']);
        });

        Schema::connection($connection)->table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });
    }
};
