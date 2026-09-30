<?php

namespace Modules\Access\Models;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Tenant-aware permission model: permissions live on the connection that
 * matches the current tenancy context.
 */
class Permission extends SpatiePermission
{
    public function getConnectionName(): ?string
    {
        return Tenant::checkCurrent()
            ? config('multitenancy.tenant_database_connection_name', 'tenant')
            : config('multitenancy.landlord_database_connection_name', 'landlord');
    }
}
