<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\QuotaManagerContract;

class TenantDashboardController extends Controller
{
    public function __construct(
        protected QuotaManagerContract $quotaManager
    ) {}

    /**
     * Display tenant workspace dashboard with key metrics and quick shortcuts.
     */
    public function index(): Response
    {
        $tenant = Tenant::current();

        $userCount = $this->quotaManager->getUserCount($tenant);
        $userLimit = $this->quotaManager->getUserLimit($tenant);
        $storageLimit = $this->quotaManager->getStorageLimitMb($tenant);
        $storageUsage = $this->quotaManager->getStorageUsageMb($tenant);

        $recentUsers = User::latest()
            ->take(5)
            ->with('roles')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'Member',
                'created_at' => $user->created_at?->format('Y-m-d') ?? '-',
            ]);

        return Inertia::render('Tenant/Dashboard', [
            'tenant' => [
                'name' => $tenant?->name ?? 'Workspace',
                'domain' => $tenant?->domain ?? 'localhost',
                'status' => $tenant?->getStatus() ?? 'active',
                'plan_name' => $tenant?->plan?->getName() ?? 'Starter',
                'trial_ends_at' => $tenant?->trial_ends_at?->format('Y-m-d'),
                'is_trialing' => $tenant?->isTrialing() ?? false,
            ],
            'quota' => [
                'users' => [
                    'current' => $userCount,
                    'limit' => $userLimit,
                    'percentage' => $userLimit ? min(100, round(($userCount / $userLimit) * 100)) : 0,
                ],
                'storage' => [
                    'current' => $storageUsage,
                    'limit' => $storageLimit,
                    'percentage' => $storageLimit ? min(100, round(($storageUsage / $storageLimit) * 100)) : 0,
                ],
            ],
            'recent_users' => $recentUsers,
        ]);
    }
}
