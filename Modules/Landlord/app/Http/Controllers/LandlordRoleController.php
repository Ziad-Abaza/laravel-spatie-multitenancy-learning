<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessInvariants;
use Modules\Access\Services\AuditWriter;
use Modules\Access\Services\ManagementPolicy;
use Modules\Access\Support\LandlordPermissions;

/**
 * Platform role management. A role can only carry permissions inside the
 * actor's own effective set; system roles are immutable; roles with holders
 * cannot be deleted. All mutations are transactional with invariant checks.
 */
class LandlordRoleController extends Controller
{
    public function __construct(
        protected ManagementPolicy $policy,
        protected AuditWriter $audit
    ) {}

    public function index(Request $request): Response
    {
        $actor = $request->user('landlord');

        $roles = Role::where('guard_name', 'landlord')
            ->with('permissions')
            ->withCount('users')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'is_system' => (bool) ($role->is_system ?? false),
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        return Inertia::render('Landlord/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->scopedGroups($actor),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user('landlord');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique(Role::class, 'name')->where('guard_name', 'landlord')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $this->policy->assertPermissionsWithinScope($actor, $validated['permissions'], 'landlord');

        $role = DB::transaction(function () use ($validated) {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'landlord']);
            $role->syncPermissions($validated['permissions']);

            return $role;
        });

        $this->audit->record($actor, 'landlord', 'role.created', 'role', $role, after: [
            'permissions' => $validated['permissions'],
        ]);

        return back()->with('success', __('role_created', ['name' => $role->name]));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $actor = $request->user('landlord');
        abort_if($role->guard_name !== 'landlord', 404);
        $this->policy->assertRoleMutable($role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique(Role::class, 'name')->where('guard_name', 'landlord')->ignore($role->id)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        // The RESULTING permission set must stay inside the actor's scope —
        // never just the delta.
        $this->policy->assertPermissionsWithinScope($actor, $validated['permissions'], 'landlord');

        $before = $role->permissions->pluck('name')->all();

        DB::transaction(function () use ($role, $validated) {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions']);
            AccessInvariants::assertManagementCapacity('landlord');
        });

        $this->audit->record($actor, 'landlord', 'role.updated', 'role', $role, before: ['permissions' => $before], after: ['permissions' => $validated['permissions']]);

        return back()->with('success', __('role_updated'));
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $actor = $request->user('landlord');
        abort_if($role->guard_name !== 'landlord', 404);
        $this->policy->assertRoleDeletable($role);

        $snapshot = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];

        DB::transaction(function () use ($role) {
            $role->delete();
            AccessInvariants::assertManagementCapacity('landlord');
        });

        $this->audit->record($actor, 'landlord', 'role.deleted', 'role', null, $role->name, before: $snapshot);

        return back()->with('success', __('role_deleted'));
    }

    /**
     * Bundle structure for the permission picker — scoped to what the actor
     * can actually delegate (groups never expose out-of-scope keys).
     *
     * @return array<int, array{key: string, label: string, permissions: array<int, array{key: string, classification: string}>}>
     */
    protected function scopedGroups(Authenticatable $actor): array
    {
        $scope = $this->policy->scopePermissions($actor, 'landlord');
        $manifest = LandlordPermissions::manifest();

        return collect(LandlordPermissions::groups())
            ->map(function ($keys, $groupKey) use ($scope, $manifest) {
                $permissions = collect($keys)
                    ->filter(fn ($key) => in_array($key, $scope, true))
                    ->map(fn ($key) => ['key' => $key, 'classification' => $manifest[$key]])
                    ->values()
                    ->all();

                return [
                    'key' => $groupKey,
                    'label' => __("perm_group_{$groupKey}"),
                    'permissions' => $permissions,
                ];
            })
            ->filter(fn ($group) => count($group['permissions']) > 0)
            ->values()
            ->all();
    }
}
