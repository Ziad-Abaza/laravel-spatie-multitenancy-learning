<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The is_public flag is a write-only dead column — no reader exists on
     * any surface, so the column is removed rather than kept ambiguous.
     */
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        if (Schema::connection($connection)->hasColumn('settings', 'is_public')) {
            Schema::connection($connection)->table('settings', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }

    public function down(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        if (! Schema::connection($connection)->hasColumn('settings', 'is_public')) {
            Schema::connection($connection)->table('settings', function (Blueprint $table) {
                $table->boolean('is_public')->default(false)->after('type');
            });
        }
    }
};
