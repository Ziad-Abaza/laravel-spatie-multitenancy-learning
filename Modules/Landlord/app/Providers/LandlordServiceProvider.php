<?php

namespace Modules\Landlord\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Landlord\Console\BackupTenantsCommand;
use Modules\Landlord\Console\EnforceTenantLifecycleCommand;
use Modules\Landlord\Console\RebuildDatabasesCommand;
use Modules\Landlord\Console\RecordTenantUsageCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LandlordServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Landlord';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'landlord';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        RebuildDatabasesCommand::class,
        EnforceTenantLifecycleCommand::class,
        BackupTenantsCommand::class,
        RecordTenantUsageCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // Lifecycle enforcement is fail-closed and idempotent — an expired
        // trial/subscription must not linger open waiting for a daily tick.
        $schedule->command(EnforceTenantLifecycleCommand::class)
            ->hourly()
            ->withoutOverlapping();

        // Per-tenant dumps — artifacts are registry-indexed, failures are
        // reported per tenant and never abort the sweep.
        $schedule->command(BackupTenantsCommand::class)
            ->daily()
            ->withoutOverlapping();

        // Metering snapshots feed billing/fair-use history — hourly keeps
        // resolution useful without bloating the series.
        $schedule->command(RecordTenantUsageCommand::class)
            ->hourly()
            ->withoutOverlapping();
    }
}
