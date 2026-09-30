<?php

namespace Modules\Access\Models;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Tenant-aware role model: roles live on the connection that matches the
 * current tenancy context, never on a connection selected at call time.
 */
class Role extends SpatieRole
{
    public function getConnectionName(): ?string
    {
        return Tenant::checkCurrent()
            ? config('multitenancy.tenant_database_connection_name', 'tenant')
            : config('multitenancy.landlord_database_connection_name', 'landlord');
    }
}
