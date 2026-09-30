<?php

namespace Modules\Landlord\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Destructive dev/maintenance reset: migrate:fresh + seed for the landlord
 * database, every tenant database, or a selected subset.
 *
 *   db:rebuild                  → landlord only
 *   db:rebuild --all            → landlord + all tenants
 *   db:rebuild --tenant=tenant1 → one tenant (id, slug, or domain)
 */
class RebuildDatabasesCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db:rebuild
                            {--all : Rebuild landlord + every tenant database}
                            {--tenant=* : Rebuild specific tenant database(s) — id, slug, or domain}
                            {--force : Force the operation to run in production}';

    protected $description = 'Drop & rebuild databases (migrate:fresh --seed) for landlord and/or tenants';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $tenants = $this->option('tenant');
        $rebuildLandlord = $this->option('all') || empty($tenants);
        $rebuildTenants = $this->option('all') || ! empty($tenants);

        if ($rebuildLandlord) {
            $this->components->task('Rebuilding landlord database', function () {
                Artisan::call('migrate:fresh', [
                    '--database' => 'landlord',
                    '--force' => true,
                    '--seed' => true,
                ]);
            });
        }

        if ($rebuildTenants) {
            foreach ($this->resolveTenants($tenants) as $tenant) {
                $this->rebuildTenant($tenant);
            }
        }

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $queries
     * @return Collection<int, Tenant>
     */
    protected function resolveTenants(array $queries): Collection
    {
        if ($this->option('all') && empty($queries)) {
            return Tenant::query()->get();
        }

        return Tenant::query()
            ->whereIn('id', $queries)
            ->orWhereIn('slug', $queries)
            ->orWhereIn('domain', $queries)
            ->get();
    }

    protected function rebuildTenant(Tenant $tenant): void
    {
        $this->components->task("Rebuilding tenant: {$tenant->domain}", function () use ($tenant) {
            try {
                $tenant->execute(function () {
                    // Hard guard: never let a tenant-schema migrate:fresh run
                    // against the landlord connection if the switch failed.
                    throw_unless(Tenant::checkCurrent(), RuntimeException::class, 'Tenant context switch failed');

                    // SwitchTenantDatabaseTask swaps the `tenant` connection's
                    // database but NOT the default connection — without
                    // --database=tenant this would wipe the landlord DB.
                    Artisan::call('migrate:fresh', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                        '--seed' => true,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->output->writeln("  <fg=yellow>skipped: {$e->getMessage()}</>", OutputInterface::VERBOSITY_NORMAL);
            }
        });
    }
}
