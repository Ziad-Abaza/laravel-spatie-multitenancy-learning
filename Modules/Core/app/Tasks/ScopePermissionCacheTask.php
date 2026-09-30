<?php

namespace Modules\Core\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Permission\PermissionRegistrar;

/**
 * Scopes the Spatie permission cache key to the current tenancy context.
 *
 * The registrar caches the full permission graph under one config key and
 * hydrated models carry their connection name — without scoping, the first
 * context to fill the cache would serve roles/permissions to every other
 * context (landlord rows visible in tenants and vice versa).
 */
class ScopePermissionCacheTask implements SwitchTenantTask
{
    public const BASE_KEY = 'spatie.permission.cache';

    public function makeCurrent(IsTenant $tenant): void
    {
        $this->scope('tenant-'.$tenant->getKey());
    }

    public function forgetCurrent(): void
    {
        $this->scope('landlord');
    }

    private function scope(string $scope): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = self::BASE_KEY.'.'.$scope;
        $registrar->clearPermissionsCollection();
    }
}
