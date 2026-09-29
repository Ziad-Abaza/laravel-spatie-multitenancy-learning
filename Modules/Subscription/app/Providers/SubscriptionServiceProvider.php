<?php

namespace Modules\Subscription\Providers;

use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Subscription\Services\QuotaService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SubscriptionServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Subscription';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'subscription';

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
        $this->app->singleton(QuotaManagerContract::class, QuotaService::class);
    }
}
