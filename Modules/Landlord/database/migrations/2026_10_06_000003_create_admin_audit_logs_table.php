<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

return new class extends Migration
{
    public function up(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->create('admin_audit_logs', function (Blueprint $table) {
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
        });
    }

    public function down(): void
    {
        if (Tenant::checkCurrent()) {
            return;
        }

        $connection = config('multitenancy.landlord_database_connection_name', 'landlord');

        Schema::connection($connection)->dropIfExists('admin_audit_logs');
    }
};
