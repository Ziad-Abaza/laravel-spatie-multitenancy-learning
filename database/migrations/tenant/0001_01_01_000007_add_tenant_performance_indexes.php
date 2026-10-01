<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes covering observed hot query patterns inside tenant databases:
     * member-directory sorting (`users.created_at` for `latest()`) and audit
     * filter+sort pagination (`action` + `created_at`).
     */
    public function up(): void
    {
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');

        Schema::connection($connection)->table('users', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::connection($connection)->table('audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');

        Schema::connection($connection)->table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::connection($connection)->table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['action', 'created_at']);
        });
    }
};
