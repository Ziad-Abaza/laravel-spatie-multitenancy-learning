<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Cache, queue and session tables in tenant databases were never read — those
 * subsystems resolve to the landlord connection. They stopped being created
 * for new tenants; this drops them from tenant databases that already ran the
 * legacy migrations. (media stays: InteractsWithMedia relations inherit the
 * owning model's connection, so tenant uploads are stored per-tenant.)
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally unrecoverable: the dropped tables were dead schema.
    }
};
