<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align tenant roles with the landlord schema — the shared Role model
     * casts is_system, so the column must exist in both contexts.
     */
    public function up(): void
    {
        // Idempotent: the base permission-tables migration creates this
        // column for new tenant databases; this migration exists for tenant
        // databases that were migrated before it was introduced.
        if (! Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropIndex(['is_system']);
                $table->dropColumn('is_system');
            });
        }
    }
};
