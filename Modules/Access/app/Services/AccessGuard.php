<?php

namespace Modules\Access\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Models\User;
use Modules\Access\Support\TenantPermissions;

/**
 * Centralized authorization rules for workspace access control.
 *
 * Every rule compares PERMISSION SETS resolved from the Spatie tables —
 * role names are never consulted for authorization decisions (they are
 * display/provisioning labels only and may be renamed freely).
 */
class AccessGuard
{
    /**
     * Actor may manage the target only when the actor's permission set covers
     * every permission the target holds. Prevents weaker-privileged users from
     * editing, demoting, suspending, or deleting more privileged accounts.
     */
    public function canManage(Authenticatable $actor, User $target): bool
    {
        return $this->permissionsOf($target)->diff($this->permissionsOf($actor))->isEmpty();
    }

    /**
     * A role may be assigned iff its entire permission set is covered by the
     * actor's own permissions. Prevents privilege escalation: a user with
     * users.create/users.update can never mint or grant a role that carries
     * permissions they themselves do not hold.
     */
    public function assertAssignableRole(Authenticatable $actor, string $roleName, string $guard): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => [__('invalid_role_selection')],
            ]);
        }

        $beyondScope = $role->permissions->pluck('name')->diff($this->permissionsOf($actor));

        if ($beyondScope->isNotEmpty()) {
            throw ValidationException::withMessages([
                'role' => [__('role_exceeds_your_permissions')],
            ]);
        }
    }

    /**
     * Permission names must exist on the given guard AND be covered by the
     * actor's own permission set — a role creator can only delegate what they
     * already hold.
     */
    public function assertPermissionsWithinScope(Authenticatable $actor, array $permissionNames, string $guard): void
    {
        $known = Permission::where('guard_name', $guard)->pluck('name');
        $unknown = collect($permissionNames)->diff($known);

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => [__('unknown_permissions_selected')],
            ]);
        }

        $beyondScope = collect($permissionNames)->diff($this->permissionsOf($actor));

        if ($beyondScope->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => [__('permissions_exceed_your_scope')],
            ]);
        }
    }

    /**
     * Whether removing the target would leave the workspace without any holder
     * of role-management capability — locking out all administration.
     */
    public function isLastManager(User $target): bool
    {
        return $target->hasPermissionTo(TenantPermissions::ROLES_UPDATE)
            && User::permission(TenantPermissions::ROLES_UPDATE)->where('id', '!=', $target->id)->doesntExist();
    }

    /**
     * Role list restricted to roles fully covered by the actor's permission
     * set — the assignable set shown in member forms.
     */
    public function assignableRoleNames(Authenticatable $actor, string $guard): array
    {
        $actorPermissions = $this->permissionsOf($actor);

        return Role::where('guard_name', $guard)
            ->with('permissions')
            ->get()
            ->filter(fn (Role $role) => $role->permissions->pluck('name')->diff($actorPermissions)->isEmpty())
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Whether the target is protected from deletion for this actor — used for
     * per-row UI flags so hidden actions match server-side enforcement.
     */
    public function isDeletableBy(Authenticatable $actor, User $target): bool
    {
        if ($target->id === $actor->getAuthIdentifier()) {
            return false;
        }

        if (! $this->canManage($actor, $target)) {
            return false;
        }

        return ! $this->isLastManager($target);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function permissionsOf(Authenticatable $user): \Illuminate\Support\Collection
    {
        return method_exists($user, 'getAllPermissions')
            ? $user->getAllPermissions()->pluck('name')
            : collect();
    }
}
