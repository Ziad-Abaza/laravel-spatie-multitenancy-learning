<?php

namespace Modules\Landlord\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Landlord\Services\TenantBackupService;

/**
 * Scheduled/manual per-tenant database dumps. Landlord-context only — each
 * artifact lands under `storage/app/backups/{slug}/` and is registered in
 * `tenant_backups`. A failed dump logs and continues; other tenants are not
 * held hostage by one broken database.
 */
class BackupTenantsCommand extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    protected $signature = 'tenants:backup {tenant? : Tenant id or slug — omit for all}';

    protected $description = 'Create database backups for one or all tenants';

    public function handle(TenantBackupService $backups): int
    {
        $query = Tenant::query();

        if ($selector = $this->argument('tenant')) {
            $query->where(fn ($q) => $q->where('id', $selector)->orWhere('slug', $selector));
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->components->warn('No tenants matched.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $backup = $backups->create($tenant);
                $this->components->info("Backup {$backup->id}: {$tenant->domain} ({$backup->size} bytes)");
            } catch (\Throwable $e) {
                $failed++;
                report($e);
                $this->components->error("Backup failed for {$tenant->domain}: {$e->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
