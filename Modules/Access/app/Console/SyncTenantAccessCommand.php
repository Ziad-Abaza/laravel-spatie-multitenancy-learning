<?php

namespace Modules\Access\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Modules\Access\Models\User;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\TenantPermissions;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Backfills/repairs tenant access control: ensures the permission catalog and
 * baseline roles exist in every tenant database, and promotes the earliest
 * user to Owner when a workspace has no privileged member at all.
 */
class SyncTenantAccessCommand extends Command
{
    use TenantAware;

    protected $signature = 'access:sync-tenants {--tenant=*} {--promote-owner= : Email of the user to promote to Owner when the workspace has no privileged member}';

    protected $description = 'Sync permission catalog and baseline roles across tenant databases';

    public function handle(): void
    {
        $tenant = Tenant::current();
        $this->info("Syncing access baseline for tenant: {$tenant->name} ({$tenant->domain})");

        try {
            app(AccessBaselineProvisioner::class)->ensureBaseline();
        } catch (QueryException $e) {
            $this->warn("  Skipped — tenant database unreachable: {$e->getMessage()}");

            return;
        }

        // "Privileged" = any user holding a management capability — judged by
        // permissions from the Spatie tables, never by role names.
        $privilegedExists = User::permission(TenantPermissions::ROLES_UPDATE)->exists()
            || User::permission(TenantPermissions::SETTINGS_MANAGE)->exists();

        if ($privilegedExists) {
            $this->line('  Privileged member already present — no promotion needed.');

            return;
        }

        $user = $this->option('promote-owner')
            ? User::where('email', $this->option('promote-owner'))->first()
            : User::orderBy('id')->first();

        if (! $user) {
            $this->warn('  No users exist in this tenant database — skipping promotion.');

            return;
        }

        $user->assignRole('Owner');
        $this->line("  Promoted {$user->email} to Owner.");
    }
}
