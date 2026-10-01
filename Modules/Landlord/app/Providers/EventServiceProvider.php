<?php

namespace Modules\Landlord\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Core\Events\PlanChanged;
use Modules\Core\Events\SubscriptionCreated;
use Modules\Core\Events\SubscriptionUpdated;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Core\Events\TenantStatusChanged;
use Modules\Landlord\Listeners\AuditTenantLifecycle;
use Modules\Landlord\Listeners\DispatchDomainEventWebhooks;
use Modules\Landlord\Listeners\SendTenantStatusNotification;
use Modules\Landlord\Listeners\SendTenantWelcomeNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        TenantCreated::class => [
            SendTenantWelcomeNotification::class,
            AuditTenantLifecycle::class,
            DispatchDomainEventWebhooks::class,
        ],
        TenantProvisioned::class => [
            DispatchDomainEventWebhooks::class,
        ],
        TenantStatusChanged::class => [
            SendTenantStatusNotification::class,
            AuditTenantLifecycle::class,
            DispatchDomainEventWebhooks::class,
        ],
        PlanChanged::class => [
            DispatchDomainEventWebhooks::class,
        ],
        SubscriptionCreated::class => [
            DispatchDomainEventWebhooks::class,
        ],
        SubscriptionUpdated::class => [
            DispatchDomainEventWebhooks::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
