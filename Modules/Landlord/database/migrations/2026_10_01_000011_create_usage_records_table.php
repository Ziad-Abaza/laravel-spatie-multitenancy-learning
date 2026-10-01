<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

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

        Schema::connection($connection)->create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('metric', 50)->index();
            $table->decimal('value', 20, 4);
            $table->timestamp('recorded_at')->index();

            $table->index(['tenant_id', 'metric', 'recorded_at']);
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

        Schema::connection($connection)->dropIfExists('usage_records');
    }
};
