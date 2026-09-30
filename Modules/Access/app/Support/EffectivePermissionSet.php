<?php

namespace Modules\Access\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Resolves the effective permission set of an actor inside ONE guard.
 *
 * Effective set = union of all permissions granted through the actor's roles.
 * Direct user→permission grants are intentionally out of scope: the project
 * never assigns them, so they are neither exposed nor considered here.
 * The guard parameter is mandatory — a permission resolvable on another guard
 * must never leak into an authorization decision.
 */
final class EffectivePermissionSet
{
    /**
     * @return Collection<int, string>
     */
    public static function of(Authenticatable|Model $actor, string $guard): Collection
    {
        if (! method_exists($actor, 'getAllPermissions')) {
            return collect();
        }

        return $actor->getAllPermissions()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->values();
    }

    /**
     * Does the actor's effective set cover the entire given set?
     *
     * @param  Collection<int, string>|array<int, string>  $required
     */
    public static function covers(Authenticatable|Model $actor, string $guard, Collection|array $required): bool
    {
        return collect($required)->diff(self::of($actor, $guard))->isEmpty();
    }
}
