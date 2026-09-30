<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessGuard;

class RoleController extends Controller
{
    public function __construct(
        protected AccessGuard $accessGuard
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

        // A role can only carry permissions the actor already holds.
        $permissions = $request->user()->getAllPermissions()->pluck('name');

        return Inertia::render('Access/Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
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
        $this->accessGuard->assertPermissionsWithinScope($request->user(), $validated['permissions'] ?? [], 'web');

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', __('role_created', ['name' => $role->name]));
    }
}
