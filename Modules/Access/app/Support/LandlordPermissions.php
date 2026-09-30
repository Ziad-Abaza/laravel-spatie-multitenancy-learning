<?php

namespace Modules\Access\Support;

/**
 * Seed manifest for platform (landlord-side) permission keys.
 *
 * Same source-of-truth boundary as TenantPermissions: the landlord
 * `permissions`/`roles` tables are the runtime authority, enforced via
 * `permission:<key>,landlord` middleware. This manifest only declares what the
 * baseline materializes. These keys are NEVER granted to tenant-side roles —
 * workspace admins operate inside their own database and guard only.
 */
final class LandlordPermissions
{
    public const GUARD = 'landlord';

    /** Baseline role name — provisioning label only, never an auth check. */
    public const ROLE_SUPER_ADMIN = 'Super Admin';

    public const TENANTS_VIEW = 'tenants.view';

    public const TENANTS_CREATE = 'tenants.create';

    public const TENANTS_UPDATE = 'tenants.update';

    public const TENANTS_LIFECYCLE = 'tenants.lifecycle';

    public const TENANTS_DELETE = 'tenants.delete';

    public const PLANS_VIEW = 'plans.view';

    public const PLANS_MANAGE = 'plans.manage';

    public const SUBSCRIPTIONS_VIEW = 'subscriptions.view';

    public const MODULES_VIEW = 'modules.view';

    public const MODULES_MANAGE = 'modules.manage';

    public const PLATFORM_SETTINGS_VIEW = 'platform.settings.view';

    public const PLATFORM_SETTINGS_MANAGE = 'platform.settings.manage';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::TENANTS_VIEW,
            self::TENANTS_CREATE,
            self::TENANTS_UPDATE,
            self::TENANTS_LIFECYCLE,
            self::TENANTS_DELETE,
            self::PLANS_VIEW,
            self::PLANS_MANAGE,
            self::SUBSCRIPTIONS_VIEW,
            self::MODULES_VIEW,
            self::MODULES_MANAGE,
            self::PLATFORM_SETTINGS_VIEW,
            self::PLATFORM_SETTINGS_MANAGE,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function roleMap(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => self::all(),
        ];
    }
}
