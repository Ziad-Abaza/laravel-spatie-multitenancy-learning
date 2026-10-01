<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\SubscriptionStatus;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Enums\UserStatus;
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

        // Estimated MRR as a single SQL aggregate — yearly subs are
        // normalized to their monthly equivalent.
        $monthlyRevenue = (float) Subscription::whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->selectRaw("SUM(CASE WHEN billing_interval = 'yearly' THEN amount / 12 ELSE amount END) as mrr")
            ->value('mrr');

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
            'mrr' => round($monthlyRevenue, 2),
            'plans_distribution' => $plansDistribution,
            'recent_tenants' => $recentTenants,
        ];
    }

    /**
     * Per-tenant support diagnostics — read-only aggregates only. A dead
     * tenant database degrades to `reachable: false` rather than throwing;
     * support needs the failure visible, not an error page.
     *
     * @return array<string, mixed>
     */
    public function tenantDiagnostics(Tenant $tenant): array
    {
        $diagnostics = [
            'reachable' => false,
            'user_count' => null,
            'active_users' => null,
            'storage_bytes' => null,
            'last_user_activity' => null,
        ];

        try {
            $result = $tenant->execute(fn () => [
                'user_count' => User::count(),
                'active_users' => User::where('status', UserStatus::Active->value)->count(),
                'storage_bytes' => (int) DB::table('media')
                    ->selectRaw('COALESCE(SUM(size), 0) as bytes')
                    ->value('bytes'),
                'last_user_activity' => User::max('updated_at'),
            ]);

            $diagnostics = array_merge($diagnostics, $result, ['reachable' => true]);
        } catch (\Throwable) {
            // Degraded state — diagnostics stay null, reachable stays false.
        }

        return $diagnostics;
    }
}
