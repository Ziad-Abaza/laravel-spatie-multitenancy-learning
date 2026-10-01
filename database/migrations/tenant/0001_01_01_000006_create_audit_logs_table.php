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

        if (! Schema::connection($connection)->hasTable('audit_logs')) {
            Schema::connection($connection)->create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('actor_guard', 20);
                // No FK — audit rows must survive deletion of the referenced user.
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('actor_label', 150);
                $table->string('action', 50)->index();
                $table->string('target_kind', 30);
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('target_label', 150)->nullable();
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->ipAddress('ip')->nullable();
                $table->timestamp('created_at')->index();

                // Audit filter + sort pagination.
                $table->index(['action', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('multitenancy.tenant_database_connection_name', 'tenant');

        Schema::connection($connection)->dropIfExists('audit_logs');
    }
};
