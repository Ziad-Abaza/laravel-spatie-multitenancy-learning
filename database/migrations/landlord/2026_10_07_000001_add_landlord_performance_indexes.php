<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

return new class extends Migration
{
    /**
     * Indexes covering observed hot query patterns on the landlord database:
     * webhook endpoint fan-out (`active`), audit filter+sort pagination
     * (`action` + `created_at`), lifecycle enforcement (`status` +
     * `trial_ends_at`), and the queue pop scan on `jobs`.
     */
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->table('webhook_endpoints', function (Blueprint $table) {
            $table->index('active');
        });

        Schema::connection($connection)->table('admin_audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at']);
        });

        Schema::connection($connection)->table('subscriptions', function (Blueprint $table) {
            $table->index(['status', 'trial_ends_at']);
            $table->index(['status', 'ends_at']);
        });

        Schema::connection($connection)->table('jobs', function (Blueprint $table) {
            $table->index(['queue', 'reserved_at', 'available_at']);
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

        Schema::connection($connection)->table('webhook_endpoints', function (Blueprint $table) {
            $table->dropIndex(['active']);
        });

        Schema::connection($connection)->table('admin_audit_logs', function (Blueprint $table) {
            $table->dropIndex(['action', 'created_at']);
        });

        Schema::connection($connection)->table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'trial_ends_at']);
            $table->dropIndex(['status', 'ends_at']);
        });

        Schema::connection($connection)->table('jobs', function (Blueprint $table) {
            $table->dropIndex(['queue', 'reserved_at', 'available_at']);
        });
    }
};
