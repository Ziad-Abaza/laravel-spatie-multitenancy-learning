<?php

namespace Modules\Access\Support;

/**
 * Seed manifest for platform (landlord-side) permission keys.
 *
 * Source-of-truth boundary: the landlord `permissions`/`roles` tables are the
 * runtime authority, enforced via `permission:<key>,landlord` middleware.
 * This class is a declaration manifest only — it defines which keys the
 * baseline provisioner materializes, how keys classify, and which capability
 * set constitutes a "management principal" for invariant checks.
 *
 * These keys are NEVER granted to tenant-side roles — workspace admins
 * operate inside their own database and guard only.
 */
final class LandlordPermissions
{
    public const GUARD = 'landlord';

    /** Baseline role name — provisioning label only, never an auth check. */
    public const ROLE_SUPER_ADMIN = 'Super Admin';

    /** Secondary baseline role — view-only platform staff. */
    public const ROLE_SUPPORT = 'Support';

    public const CLASS_READ = 'read';

    public const CLASS_WRITE = 'write';

    public const CLASS_SENSITIVE = 'sensitive';

    public const TENANTS_VIEW = 'tenants.view';

    public const TENANTS_CREATE = 'tenants.create';

    public const TENANTS_UPDATE = 'tenants.update';

    public const TENANTS_LIFECYCLE = 'tenants.lifecycle';

    public const TENANTS_DELETE = 'tenants.delete';

    public const TENANTS_EXPORT = 'tenants.export';

    public const PLANS_VIEW = 'plans.view';

    public const PLANS_MANAGE = 'plans.manage';

    public const SUBSCRIPTIONS_VIEW = 'subscriptions.view';

    public const MODULES_VIEW = 'modules.view';

    public const MODULES_MANAGE = 'modules.manage';

    public const PLATFORM_SETTINGS_VIEW = 'platform.settings.view';

    public const PLATFORM_SETTINGS_MANAGE = 'platform.settings.manage';

    public const ADMINS_VIEW = 'admins.view';

    public const ADMINS_CREATE = 'admins.create';

    public const ADMINS_UPDATE = 'admins.update';

    public const ADMINS_ASSIGN_ROLE = 'admins.assign-role';

    public const ADMINS_RESET_PASSWORD = 'admins.reset-password';

    public const ADMINS_SUSPEND = 'admins.suspend';

    public const ADMINS_DELETE = 'admins.delete';

    public const ROLES_VIEW = 'roles.view';

    public const ROLES_MANAGE = 'roles.manage';

    public const AUDIT_VIEW = 'audit.view';

    public const METRICS_VIEW = 'metrics.view';

    public const WEBHOOKS_VIEW = 'webhooks.view';

    public const WEBHOOKS_MANAGE = 'webhooks.manage';

    /**
     * Key → classification. 'read' keys compose the view-only preset and the
     * Support baseline role; 'sensitive' keys are irreversible or
     * privilege-affecting operations.
     *
     * @return array<string, string>
     */
    public static function manifest(): array
    {
        return [
            self::TENANTS_VIEW => self::CLASS_READ,
            self::TENANTS_CREATE => self::CLASS_WRITE,
            self::TENANTS_UPDATE => self::CLASS_WRITE,
            self::TENANTS_LIFECYCLE => self::CLASS_SENSITIVE,
            self::TENANTS_DELETE => self::CLASS_SENSITIVE,
            self::TENANTS_EXPORT => self::CLASS_SENSITIVE,
            self::PLANS_VIEW => self::CLASS_READ,
            self::PLANS_MANAGE => self::CLASS_WRITE,
            self::SUBSCRIPTIONS_VIEW => self::CLASS_READ,
            self::MODULES_VIEW => self::CLASS_READ,
            self::MODULES_MANAGE => self::CLASS_SENSITIVE,
            self::PLATFORM_SETTINGS_VIEW => self::CLASS_READ,
            self::PLATFORM_SETTINGS_MANAGE => self::CLASS_SENSITIVE,
            self::ADMINS_VIEW => self::CLASS_READ,
            self::ADMINS_CREATE => self::CLASS_SENSITIVE,
            self::ADMINS_UPDATE => self::CLASS_WRITE,
            self::ADMINS_ASSIGN_ROLE => self::CLASS_SENSITIVE,
            self::ADMINS_RESET_PASSWORD => self::CLASS_SENSITIVE,
            self::ADMINS_SUSPEND => self::CLASS_SENSITIVE,
            self::ADMINS_DELETE => self::CLASS_SENSITIVE,
            self::ROLES_VIEW => self::CLASS_READ,
            self::ROLES_MANAGE => self::CLASS_SENSITIVE,
            self::AUDIT_VIEW => self::CLASS_READ,
            self::METRICS_VIEW => self::CLASS_READ,
            self::WEBHOOKS_VIEW => self::CLASS_READ,
            self::WEBHOOKS_MANAGE => self::CLASS_SENSITIVE,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::manifest());
    }

    /**
     * The pinned capability set that defines a "management principal" for
     * invariant checks — an active admin who can keep the platform's access
     * layer operable. Deliberately stable: adding new permissions to the
     * catalog must NOT change the baseline.
     *
     * @return array<int, string>
     */
    public static function managementBaseline(): array
    {
        return [
            self::ADMINS_UPDATE,
            self::ADMINS_ASSIGN_ROLE,
            self::ROLES_MANAGE,
        ];
    }

    /**
     * Groupings DERIVED from the manifest by resource prefix — they never
     * define new keys.
     *
     * @return array<string, array<int, string>>
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::manifest() as $key => $classification) {
            $groups[explode('.', $key)[0]][] = $key;
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    public static function readOnly(): array
    {
        return array_keys(array_filter(self::manifest(), fn ($class) => $class === self::CLASS_READ));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function roleMap(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => self::all(),
            self::ROLE_SUPPORT => self::readOnly(),
        ];
    }
}
