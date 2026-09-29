<?php

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
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');

        if (empty(config("database.connections.{$connection}.database"))) {
            return;
        }

        if (! Schema::connection($connection)->hasTable('tenant_settings')) {
            Schema::connection($connection)->create('tenant_settings', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->default('general')->index();
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->boolean('is_public')->default(false);
                $table->timestamps();

                $table->unique(['domain', 'key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');

        Schema::connection($connection)->dropIfExists('tenant_settings');
    }
};
