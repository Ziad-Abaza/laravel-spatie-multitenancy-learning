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

        Schema::connection($connection)->create('tenant_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('driver', 20);
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 20)->default('completed')->index();
            $table->timestamp('created_at')->index();
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

        Schema::connection($connection)->dropIfExists('tenant_backups');
    }
};
