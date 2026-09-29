<?php

namespace Modules\Landlord\Services;

use Modules\Core\Enums\SubscriptionStatus;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\Tenant;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\Subscription;

class LandlordMetricsService
{
    /**
     * Gather system KPIs and overview analytics for the Landlord dashboard.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::whereIn('status', [TenantStatus::Active, TenantStatus::Trialing])->count();
        $suspendedTenants = Tenant::where('status', TenantStatus::Suspended)->count();

        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::Active)->count();
        $trialingSubscriptions = Subscription::where('status', SubscriptionStatus::Trialing)->count();

        // Calculate estimated MRR
        $monthlyRevenue = Subscription::whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->get()
            ->sum(function (Subscription $subscription) {
                if ($subscription->billing_interval === 'yearly') {
                    return $subscription->amount / 12;
                }

                return $subscription->amount;
            });

        // Distribution by plan
        $plansDistribution = Plan::withCount('tenants')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'count' => $plan->tenants_count,
                'price' => $plan->price,
            ]);

        // Recent tenants
        $recentTenants = Tenant::with('plan')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'status' => $tenant->getStatus(),
                'plan_name' => $tenant->plan?->name ?? 'Free',
                'created_at' => $tenant->created_at?->format('Y-m-d H:i') ?? '-',
            ]);

        return [
            'total_tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'suspended_tenants' => $suspendedTenants,
            'active_subscriptions' => $activeSubscriptions,
            'trialing_subscriptions' => $trialingSubscriptions,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'plans_distribution' => $plansDistribution,
            'recent_tenants' => $recentTenants,
        ];
    }
}
