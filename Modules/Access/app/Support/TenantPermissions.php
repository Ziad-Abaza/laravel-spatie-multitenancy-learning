<?php

namespace Modules\Access\Support;

/**
 * Seed manifest for workspace (tenant-side) permission keys.
 *
 * IMPORTANT — source-of-truth boundary:
 * - The `permissions`/`roles` tables inside each tenant database are the
 *   runtime source of truth for authorization. All enforcement goes through
 *   Spatie's `permission:` middleware, which resolves grants from the table.
 * - This class is a declaration manifest only: it defines which keys the
 *   baseline provisioner materializes into the table and which baseline roles
 *   hold them at seeding time. It must never be used for runtime
 *   authorization decisions — use `$user->can()` / `permission:` middleware.
 * - Tenant permission keys describe workspace-level capabilities ONLY.
 *   Platform/landlord capabilities (managing tenants, plans, modules, or
 *   platform settings) are enforced on the landlord guard and landlord
 *   database — tenant roles must never grant them.
 */
final class TenantPermissions
{
    /**
     * Baseline role names — provisioning labels only. Renaming them is safe
     * because no authorization decision ever compares role names; only the
     * permission sets attached in the Spatie tables matter.
     */
    public const ROLE_OWNER = 'Owner';

    public const ROLE_ADMIN = 'Admin';

    public const ROLE_MEMBER = 'Member';

    public const USERS_VIEW = 'users.view';

    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_DELETE = 'users.delete';

    public const ROLES_VIEW = 'roles.view';

    public const ROLES_CREATE = 'roles.create';

    public const ROLES_UPDATE = 'roles.update';

    public const ROLES_DELETE = 'roles.delete';

    public const SUBSCRIPTION_VIEW = 'subscription.view';

    public const SUBSCRIPTION_MANAGE = 'subscription.manage';

    public const SETTINGS_VIEW = 'settings.view';

    public const SETTINGS_MANAGE = 'settings.manage';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_DELETE,
            self::ROLES_VIEW,
            self::ROLES_CREATE,
            self::ROLES_UPDATE,
            self::ROLES_DELETE,
            self::SUBSCRIPTION_VIEW,
            self::SUBSCRIPTION_MANAGE,
            self::SETTINGS_VIEW,
            self::SETTINGS_MANAGE,
        ];
    }

    /**
     * Permissions granted per baseline tenant role.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleMap(): array
    {
        return [
            self::ROLE_OWNER => self::all(),
            self::ROLE_ADMIN => self::all(),
            self::ROLE_MEMBER => [self::USERS_VIEW],
        ];
    }
}
