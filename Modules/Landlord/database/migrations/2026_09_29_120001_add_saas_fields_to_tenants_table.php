<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->table('tenants', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (! Schema::connection($connection)->hasColumn('tenants', 'status')) {
                $table->string('status')->default('active')->index()->after('database');
            }
            if (! Schema::connection($connection)->hasColumn('tenants', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->index()->after('status');
            }
            if (! Schema::connection($connection)->hasColumn('tenants', 'settings')) {
                $table->json('settings')->nullable()->after('plan_id');
            }
            if (! Schema::connection($connection)->hasColumn('tenants', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('settings');
            }
            if (! Schema::connection($connection)->hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'status', 'plan_id', 'settings', 'trial_ends_at', 'suspended_at']);
        });
    }
};
