<?php

namespace Modules\Landlord\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Landlord\Console\RebuildDatabasesCommand;
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
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
