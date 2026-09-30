<?php

namespace Modules\Access\Services;

use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Support\LandlordPermissions;
use Modules\Access\Support\TenantPermissions;

/**
 * Ensures the permission catalog and baseline roles exist inside the
 * currently active tenant database. Idempotent — safe to run at tenant
 * provisioning time and for backfilling existing tenant databases.
 */
class AccessBaselineProvisioner
{
    public const GUARD = 'web';

    public function ensureBaseline(): void
    {
        foreach (TenantPermissions::all() as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        foreach (TenantPermissions::roleMap() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, self::GUARD);
            $role->syncPermissions($permissions);
        }
    }

    /**
     * Landlord-side baseline. Runs on the landlord connection (no current
     * tenant) — platform permission catalog plus the Super Admin role.
     */
    public function ensureLandlordBaseline(): void
    {
        foreach (LandlordPermissions::all() as $permission) {
            Permission::findOrCreate($permission, LandlordPermissions::GUARD);
        }

        foreach (LandlordPermissions::roleMap() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, LandlordPermissions::GUARD);
            $role->syncPermissions($permissions);
        }
    }
}
