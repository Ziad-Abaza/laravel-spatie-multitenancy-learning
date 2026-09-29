<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display listing of roles and permissions in current tenant scope.
     */
    public function index(): Response
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

        $permissions = Permission::where('guard_name', 'web')->pluck('name');

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
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
        ]);

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
