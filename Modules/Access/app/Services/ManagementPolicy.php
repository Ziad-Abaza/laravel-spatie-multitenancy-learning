<?php

namespace Modules\Access\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Support\EffectivePermissionSet;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Single decision point for access-control mutations on BOTH guards.
 *
 * Every rule compares effective permission sets resolved from the Spatie
 * tables — role names are never consulted for authorization (they are
 * display/provisioning labels). The policy answers "is this allowed?" and
 * throws; it performs no mutation and no audit writes.
 */
class ManagementPolicy
{
    /**
     * Target's CURRENT effective set must be covered by the actor's — the
     * actor cannot manage a principal holding capabilities they lack.
     */
    public function canManage(Authenticatable $actor, Model $target, string $guard): bool
    {
        return EffectivePermissionSet::covers($actor, $guard, EffectivePermissionSet::of($target, $guard));
    }

    public function assertCanManage(Authenticatable $actor, Model $target, string $guard): void
    {
        if (! $this->canManage($actor, $target, $guard)) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * For granting a role to a not-yet-existing principal (user creation):
     * the role exists on this guard and its full permission set is covered
     * by the actor's effective set.
     */
    public function assertRoleGrantable(Authenticatable $actor, string $roleName, string $guard): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => [__('invalid_role_selection')],
            ]);
        }

        if ($role->permissions->pluck('name')->diff(EffectivePermissionSet::of($actor, $guard))->isNotEmpty()) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * Role may be granted iff the target's RESULTING effective set stays
     * covered by the actor's own. Checking the resulting set — not the
     * current one — closes the "grant then escalate" hole.
     */
    public function assertAssignableRole(Authenticatable $actor, Model $target, string $roleName, string $guard): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => [__('invalid_role_selection')],
            ]);
        }

        $this->assertCanManage($actor, $target, $guard);

        $resulting = EffectivePermissionSet::of($target, $guard)
            ->merge($role->permissions->pluck('name'))
            ->unique();

        if ($resulting->diff(EffectivePermissionSet::of($actor, $guard))->isNotEmpty()) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * For role creation/update: every submitted permission must exist on this
     * guard (422 otherwise) and the RESULTING set must be covered by the
     * actor's effective set (403 otherwise — generic message, never reveals
     * cross-guard catalog details).
     *
     * @param  array<int, string>  $permissionNames
     */
    public function assertPermissionsWithinScope(Authenticatable $actor, array $permissionNames, string $guard): void
    {
        $known = Permission::where('guard_name', $guard)->pluck('name');

        if (collect($permissionNames)->diff($known)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => [__('unknown_permissions_selected')],
            ]);
        }

        if (collect($permissionNames)->diff(EffectivePermissionSet::of($actor, $guard))->isNotEmpty()) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * System roles are immutable: no rename, no permission edits, no delete.
     */
    public function assertRoleMutable(Role $role): void
    {
        if ($role->is_system) {
            throw new AccessDeniedHttpException(__('system_role_immutable'));
        }
    }

    /**
     * A role with ANY holders (active or suspended) cannot be deleted —
     * assignments are authorization state, not session state.
     */
    public function assertRoleDeletable(Role $role): void
    {
        $this->assertRoleMutable($role);

        if ($role->users()->exists()) {
            throw new HttpException(409, __('role_in_use'));
        }
    }

    /**
     * Self-targeting mutations that would change access level are forbidden.
     */
    public function assertNotSelfMutation(Authenticatable $actor, Model $target): void
    {
        if ($target->is($actor)) {
            throw new AccessDeniedHttpException(__('cannot_change_own_role_or_status'));
        }
    }

    /**
     * Whether the target can be deleted by this actor: covered scope, not
     * self, and removal must not orphan the last management principal.
     */
    public function isDeletableBy(Authenticatable $actor, Model $target, string $guard): bool
    {
        if ($target->is($actor)) {
            return false;
        }

        if (! $this->canManage($actor, $target, $guard)) {
            return false;
        }

        if (EffectivePermissionSet::covers($target, $guard, AccessInvariants::baselineFor($guard))) {
            return AccessInvariants::principalsQuery($guard)
                ->whereKeyNot($target->getKey())
                ->exists();
        }

        return true;
    }

    /**
     * Roles fully covered by the actor's effective set — the assignable set.
     *
     * @return array<int, string>
     */
    public function assignableRoleNames(Authenticatable $actor, string $guard): array
    {
        $actorPermissions = EffectivePermissionSet::of($actor, $guard);

        return Role::where('guard_name', $guard)
            ->with('permissions')
            ->get()
            ->filter(fn (Role $role) => $role->permissions->pluck('name')->diff($actorPermissions)->isEmpty())
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Effective permission names of the actor — the delegation ceiling shown
     * to role builders.
     *
     * @return array<int, string>
     */
    public function scopePermissions(Authenticatable $actor, string $guard): array
    {
        return EffectivePermissionSet::of($actor, $guard)->all();
    }
}
