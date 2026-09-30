<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessGuard;
use Modules\Access\Services\TenantUserService;
use Modules\Core\Contracts\QuotaManagerContract;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserService $userService,
        protected QuotaManagerContract $quotaManager,
        protected AccessGuard $accessGuard
    ) {}

    /**
     * Display directory of team members inside tenant workspace.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $actor = $request->user();

        $users = $this->userService->listUsers($search)->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'job_title' => $user->job_title ?? '-',
            'phone' => $user->phone ?? '-',
            'status' => $user->status ?? 'active',
            'role' => $user->roles->first()?->name ?? 'Member',
            'avatar_url' => $user->getAvatarUrl(),
            'created_at' => $user->created_at?->format('Y-m-d') ?? '-',
            'can_edit' => $this->accessGuard->canManage($actor, $user),
            'can_delete' => $this->accessGuard->isDeletableBy($actor, $user),
        ]);

        // Only roles fully covered by the actor's permissions may be assigned.
        $roles = $this->accessGuard->assignableRoleNames($actor, 'web');

        $currentTenant = Tenant::current();
        $userLimit = $this->quotaManager->getUserLimit($currentTenant);
        $userCount = $this->quotaManager->getUserCount($currentTenant);

        return Inertia::render('Access/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'quota' => [
                'current' => $userCount,
                'limit' => $userLimit,
                'can_add' => $this->quotaManager->canAddUser($currentTenant),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store new team member in tenant database with quota checking.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists(Role::class, 'name')->where('guard_name', 'web')],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        // Role names are opaque labels — assignability is proven by comparing
        // the role's permission set against the actor's own permissions.
        $this->accessGuard->assertAssignableRole($request->user(), $validated['role'], 'web');

        $this->userService->createUser($validated);

        return back()->with('success', __('member_invited'));
    }

    /**
     * Update team member info or role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', Rule::exists(Role::class, 'name')->where('guard_name', 'web')],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
        ]);

        $actor = $request->user();

        // Account hijack protection: the actor must cover every permission
        // the target holds before modifying it (e.g. resetting credentials).
        if (! $this->accessGuard->canManage($actor, $user)) {
            abort(403);
        }

        if (! empty($validated['role'])) {
            $this->accessGuard->assertAssignableRole($actor, $validated['role'], 'web');
        }

        // Self-lockout protection: you cannot change your own role or status.
        if ($user->id === $actor->getAuthIdentifier()
            && (! empty($validated['role']) && $validated['role'] !== $user->roles->first()?->name
                || isset($validated['status']) && $validated['status'] !== $user->status)) {
            throw ValidationException::withMessages([
                'email' => [__('cannot_change_own_role_or_status')],
            ]);
        }

        $this->userService->updateUser($user, $validated);

        return back()->with('success', __('user_updated'));
    }

    /**
     * Delete team member from tenant.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', __('cannot_delete_own_account'));
        }

        if (! $this->accessGuard->canManage($request->user(), $user)) {
            return back()->with('error', __('cannot_manage_more_privileged_user'));
        }

        if ($this->accessGuard->isLastManager($user)) {
            return back()->with('error', __('cannot_delete_workspace_owner'));
        }

        $this->userService->deleteUser($user);

        return back()->with('success', __('user_removed'));
    }
}
