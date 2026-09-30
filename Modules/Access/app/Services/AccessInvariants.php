<?php

namespace Modules\Access\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Access\Support\LandlordPermissions;
use Modules\Access\Support\TenantPermissions;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Critical Management Invariant:
 *
 *   count(active principals whose effective permission set covers the
 *   guard's management baseline) >= 1
 *
 * The baseline is a pinned capability set per guard (see *Permissions::
 * managementBaseline) — it does not shift when new permissions are added.
 * Principals are evaluated on their effective union across ALL roles, never
 * on a single permission or role name.
 *
 * Call inside the same transaction as the mutation being validated; the
 * holders query runs under lockForUpdate so concurrent demotion/deletion of
 * the last managers serializes on the pivot rows instead of both passing.
 */
class AccessInvariants
{
    /**
     * The user model backing a guard — resolved from the auth provider so
     * morph types in model_has_roles always match the canonical class.
     *
     * @return class-string<Model>
     */
    public static function userModelFor(string $guard): string
    {
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model");
    }

    /**
     * @return array<int, string>
     */
    public static function baselineFor(string $guard): array
    {
        return match ($guard) {
            LandlordPermissions::GUARD => LandlordPermissions::managementBaseline(),
            default => TenantPermissions::managementBaseline(),
        };
    }

    /**
     * Principals: users whose roles (via tenant/landlord pivot tables) grant
     * EVERY baseline permission. Locked when invoked in a transaction.
     */
    public static function principalsQuery(string $guard, bool $forUpdate = false): Builder
    {
        $model = self::userModelFor($guard);
        $query = $model::query();

        foreach (self::baselineFor($guard) as $permission) {
            $query->whereHas('roles.permissions', fn (Builder $q) => $q->where('name', $permission));
        }

        // Both user tables carry a `status` column (tenant: baseline users
        // table; landlord: added by migration). Active principals only.
        $query->where(fn (Builder $q) => $q->whereNull('status')->orWhere('status', 'active'));

        return $forUpdate ? $query->lockForUpdate() : $query;
    }

    /**
     * Throws 409 when the workspace/platform would be left without any
     * active management principal. Must run inside the mutation transaction.
     */
    public static function assertManagementCapacity(string $guard): void
    {
        if (self::principalsQuery($guard, forUpdate: true)->doesntExist()) {
            throw new HttpException(409, __('management_invariant_violation'));
        }
    }
}
