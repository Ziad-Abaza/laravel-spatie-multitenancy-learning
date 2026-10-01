<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\Role;
use Modules\Access\Services\AuditWriter;
use Modules\Access\Services\ManagementPolicy;
use Modules\Access\Support\TenantPermissions;

class RoleController extends Controller
{
    public function __construct(
        protected ManagementPolicy $policy,
        protected AuditWriter $audit
    ) {}

    /**
     * Display listing of roles and permissions in current tenant scope.
     */
    public function index(Request $request): Response
    {
        $roles = Role::where('guard_name', 'web')
            ->with('permissions')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name'),
            ]);

        // Bundle structure for the picker — scoped to what the actor can
        // actually delegate; groups never expose out-of-scope keys.
        $scope = $this->policy->scopePermissions($request->user(), 'web');
        $manifest = TenantPermissions::manifest();

        $permissionGroups = collect(TenantPermissions::groups())
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

        return Inertia::render('Access/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique(Role::class, 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
        ]);

        // Server-side scope check: a role may only carry permissions the
        // actor already holds — prevents minting a superset role.
        $this->policy->assertPermissionsWithinScope($request->user(), $validated['permissions'] ?? [], 'web');

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $this->audit->record(
            $request->user(), 'web', 'role.created', 'role', $role, $role->name,
            after: ['name' => $role->name, 'permissions' => $validated['permissions'] ?? []],
        );

        return back()->with('success', __('role_created', ['name' => $role->name]));
    }
}
