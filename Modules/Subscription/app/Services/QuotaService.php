<?php

namespace Modules\Subscription\Services;

use App\Models\User;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Landlord\Models\Tenant;

class QuotaService implements QuotaManagerContract
{
    /**
     * Check if a new user can be created under the tenant plan quota.
     */
    public function canAddUser(mixed $tenant): bool
    {
        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::current();
        }

        if (! $tenant) {
            return true;
        }

        $limit = $this->getUserLimit($tenant);

        if ($limit === null) {
            return true; // Unlimited
        }

        $currentCount = $this->getUserCount($tenant);

        return $currentCount < $limit;
    }

    /**
     * Check if the tenant has permission to use a specific feature.
     */
    public function canUseFeature(mixed $tenant, string $feature): bool
    {
        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::current();
        }

        if (! $tenant) {
            return true;
        }

        return $tenant->hasFeature($feature);
    }

    /**
     * Get maximum allowed users for the tenant plan.
     */
    public function getUserLimit(mixed $tenant): ?int
    {
        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::current();
        }

        if (! $tenant || ! $tenant->plan) {
            return 5; // Default free limit
        }

        $limit = $tenant->plan->getLimit('max_users');

        return is_numeric($limit) ? (int) $limit : null;
    }

    /**
     * Get the current count of users in the tenant database.
     */
    public function getUserCount(mixed $tenant): int
    {
        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::current();
        }

        if (! $tenant) {
            return 0;
        }

        return $tenant->execute(function () {
            try {
                return User::count();
            } catch (\Throwable) {
                return 0;
            }
        });
    }

    /**
     * Get maximum storage allowed in megabytes.
     */
    public function getStorageLimitMb(mixed $tenant): ?int
    {
        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::current();
        }

        if (! $tenant || ! $tenant->plan) {
            return 1024;
        }

        $limit = $tenant->plan->getLimit('max_storage_mb');

        return is_numeric($limit) ? (int) $limit : null;
    }
}
