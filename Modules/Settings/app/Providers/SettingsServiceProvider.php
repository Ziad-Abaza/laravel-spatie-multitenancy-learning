<?php

namespace Modules\Settings\Providers;

use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Services\SettingService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Settings';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'settings';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

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
    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(module_path($this->name, 'config/settings.php'), 'settings');
        // Concrete registered as the singleton and the contract aliased to
        // it — both `app(SettingService::class)` and `app(Contract::class)`
        // must resolve the same instance so per-request memoization is shared.
        $this->app->singleton(SettingService::class);
        $this->app->alias(SettingService::class, SettingManagerContract::class);
    }
}
