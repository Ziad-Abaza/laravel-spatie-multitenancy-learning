<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Services\TenantUserService;
use Modules\Core\Contracts\QuotaManagerContract;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserService $userService,
        protected QuotaManagerContract $quotaManager
    ) {}

    /**
     * Display directory of team members inside tenant workspace.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

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
        ]);

        $roles = Role::where('guard_name', 'web')->pluck('name');

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
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

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
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
        ]);

        $this->userService->updateUser($user, $validated);

        return back()->with('success', __('user_updated'));
    }

    /**
     * Delete team member from tenant.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('cannot_delete_own_account'));
        }

        $this->userService->deleteUser($user);

        return back()->with('success', __('user_removed'));
    }
}
