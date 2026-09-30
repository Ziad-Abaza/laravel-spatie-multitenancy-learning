<?php

namespace Modules\Access\Services;

use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Support\LandlordPermissions;
use Modules\Access\Support\TenantPermissions;
use Spatie\Permission\PermissionRegistrar;

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

        // Fresh-created permissions must be visible to findByName within the
        // same process — drop the registrar cache before attaching.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (LandlordPermissions::roleMap() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, LandlordPermissions::GUARD);
            $role->syncPermissions($permissions);

            if ($roleName === LandlordPermissions::ROLE_SUPER_ADMIN && ! $role->is_system) {
                $role->forceFill(['is_system' => true])->save();
            }
        }
    }
}
