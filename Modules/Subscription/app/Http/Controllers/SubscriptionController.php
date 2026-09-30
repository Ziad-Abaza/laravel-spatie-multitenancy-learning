<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected QuotaManagerContract $quotaManager
    ) {}

    /**
     * Landlord view: list all tenant subscriptions across platform.
     */
    public function landlordIndex(): Response
    {
        $subscriptions = Subscription::with(['tenant', 'plan'])
            ->latest()
            ->paginate(15)
            ->through(fn (Subscription $sub) => [
                'id' => $sub->id,
                'tenant_name' => $sub->tenant?->name ?? 'Unknown',
                'tenant_domain' => $sub->tenant?->domain ?? '-',
                'plan_name' => $sub->plan?->getName() ?? '-',
                'status' => $sub->getStatus(),
                'amount' => $sub->amount,
                'currency' => $sub->currency,
                'billing_interval' => $sub->billing_interval,
                'starts_at' => $sub->starts_at?->format('Y-m-d') ?? '-',
                'ends_at' => $sub->ends_at?->format('Y-m-d') ?? '-',
                'trial_ends_at' => $sub->trial_ends_at?->format('Y-m-d') ?? null,
            ]);

        return Inertia::render('Subscription/LandlordSubscriptions', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Tenant view: display subscription overview, limits, usage, and upgrade options.
     */
    public function tenantOverview(): Response
    {
        $tenant = Tenant::current();

        $currentPlan = $tenant?->plan;
        $currentSubscription = $tenant?->currentSubscription;

        $userCount = $this->quotaManager->getUserCount($tenant);
        $userLimit = $this->quotaManager->getUserLimit($tenant);
        $storageLimit = $this->quotaManager->getStorageLimitMb($tenant);
        $storageUsage = $this->quotaManager->getStorageUsageMb($tenant);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->getName(),
                'slug' => $plan->slug,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'limits' => $plan->limits,
                'is_current' => $currentPlan && $currentPlan->id === $plan->id,
            ]);

        return Inertia::render('Subscription/Overview', [
            'plan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->getName(),
                'price' => $currentPlan->price,
                'currency' => $currentPlan->currency,
                'limits' => $currentPlan->limits,
            ] : null,
            'subscription' => $currentSubscription ? [
                'id' => $currentSubscription->id,
                'status' => $currentSubscription->getStatus(),
                'billing_interval' => $currentSubscription->billing_interval,
                'amount' => $currentSubscription->amount,
                'trial_ends_at' => $currentSubscription->trial_ends_at?->format('Y-m-d'),
                'ends_at' => $currentSubscription->ends_at?->format('Y-m-d'),
            ] : null,
            'usage' => [
                'users' => [
                    'current' => $userCount,
                    'limit' => $userLimit,
                    'percentage' => $userLimit ? min(100, round(($userCount / $userLimit) * 100)) : 0,
                ],
                'storage_mb' => [
                    'current' => $storageUsage,
                    'limit' => $storageLimit,
                    'percentage' => $storageLimit ? min(100, round(($storageUsage / $storageLimit) * 100)) : 0,
                ],
            ],
            'available_plans' => $plans,
        ]);
    }

    /**
     * Process tenant subscription upgrade or plan change.
     */
    public function changePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $tenant = Tenant::current();
        if (! $tenant) {
            abort(404);
        }

        $newPlan = Plan::findOrFail($validated['plan_id']);

        $this->subscriptionService->changePlan($tenant, $newPlan);

        return back()->with('success', __('plan_changed', ['plan' => $newPlan->getName()]));
    }
}
