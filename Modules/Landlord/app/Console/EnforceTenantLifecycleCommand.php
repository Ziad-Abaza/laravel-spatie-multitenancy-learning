<?php

namespace Modules\Landlord\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Core\Enums\SubscriptionStatus;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Services\TenantLifecycleService;
use Modules\Subscription\Models\Subscription;

/**
 * Fail-closed lifecycle enforcement: an expired trial or lapsed subscription
 * suspends the workspace. All mutations go through TenantLifecycleService so
 * the TenantStatus state machine remains the single authority on transitions.
 */
class EnforceTenantLifecycleCommand extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    protected $signature = 'tenants:enforce-lifecycle';

    protected $description = 'Suspend tenants whose trial or subscription has expired';

    public function handle(TenantLifecycleService $lifecycle): int
    {
        Tenant::query()
            ->where('status', TenantStatus::Trialing->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get()
            ->each(function (Tenant $tenant) use ($lifecycle) {
                $lifecycle->suspend($tenant, 'Trial period expired');
                $this->components->info("Suspended expired trial: {$tenant->domain}");
            });

        Subscription::query()
            ->with('tenant')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get()
            ->each(function (Subscription $subscription) use ($lifecycle) {
                $subscription->update(['status' => SubscriptionStatus::Expired]);

                $tenant = $subscription->tenant;
                if ($tenant !== null
                    && TenantStatus::from($tenant->getStatus())->canTransitionTo(TenantStatus::Suspended)) {
                    $lifecycle->suspend($tenant, 'Subscription expired');
                    $this->components->info("Suspended lapsed subscription: {$tenant->domain}");
                }
            });

        // Retention sweep: archived tenants past their grace window are
        // export-backed-up then purged — irreversible but never silent.
        $purged = $lifecycle->purgeExpiredRetentions();
        if ($purged > 0) {
            $this->components->info("Purged {$purged} tenant(s) past their retention window.");
        }

        return self::SUCCESS;
    }
}
