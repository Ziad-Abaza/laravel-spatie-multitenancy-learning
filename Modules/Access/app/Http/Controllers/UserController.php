<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessInvariants;
use Modules\Access\Services\AuditWriter;
use Modules\Access\Services\ManagementPolicy;
use Modules\Access\Services\TenantUserService;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Core\Enums\UserStatus;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserService $userService,
        protected QuotaManagerContract $quotaManager,
        protected ManagementPolicy $policy,
        protected AuditWriter $audit
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
            'status' => $user->status ?? UserStatus::Active->value,
            'role' => $user->roles->first()?->name ?? TenantPermissions::ROLE_MEMBER,
            'avatar_url' => $user->getAvatarUrl(),
            'created_at' => $user->created_at?->format('Y-m-d') ?? '-',
            'can_edit' => $this->policy->canManage($actor, $user, 'web'),
            'can_delete' => $this->policy->isDeletableBy($actor, $user, 'web'),
        ]);

        // Only roles fully covered by the actor's permissions may be assigned.
        $roles = $this->policy->assignableRoleNames($actor, 'web');

        $currentTenant = Tenant::current();
        $userLimit = $this->quotaManager->getUserLimit($currentTenant);
        $userCount = $this->quotaManager->getUserCount($currentTenant);

        return Inertia::render('Access/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'quota' => [
                'current' => $userCount,
                'limit' => $userLimit,
                'can_add' => $userLimit === null || $userCount < $userLimit,
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

        // Role names are opaque labels — grantability is proven by comparing
        // the role's permission set against the actor's effective set.
        $this->policy->assertRoleGrantable($request->user(), $validated['role'], 'web');

        $user = DB::transaction(function () use ($validated, $request) {
            $user = $this->userService->createUser($validated);

            $this->audit->record(
                $request->user(), 'web', 'user.created', 'user', $user, $user->email,
                after: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $validated['role'],
                ],
            );

            return $user;
        });

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
            'status' => ['nullable', 'string', Rule::enum(UserStatus::class)],
        ]);

        $actor = $request->user();

        // Account hijack protection: the actor must cover every permission
        // the target holds before modifying it (e.g. resetting credentials).
        if (! $this->policy->canManage($actor, $user, 'web')) {
            abort(403);
        }

        if (! empty($validated['role'])) {
            $this->policy->assertAssignableRole($actor, $user, $validated['role'], 'web');
        }

        // Self-lockout protection: you cannot change your own role or status.
        if ($user->is($actor)
            && (! empty($validated['role']) && $validated['role'] !== $user->roles->first()?->name
                || isset($validated['status']) && $validated['status'] !== $user->status)) {
            throw ValidationException::withMessages([
                'email' => [__('cannot_change_own_role_or_status')],
            ]);
        }

        $accessChanged = ! empty($validated['role']) || isset($validated['status']);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->roles->first()?->name,
        ];

        DB::transaction(function () use ($user, $validated, $accessChanged, $before, $request) {
            $this->userService->updateUser($user, $validated);

            $fresh = $user->fresh(['roles']);

            $this->audit->record(
                $request->user(), 'web', 'user.updated', 'user', $user, $user->email,
                before: $before,
                after: [
                    'name' => $fresh->name,
                    'email' => $fresh->email,
                    'status' => $fresh->status,
                    'role' => $fresh->roles->first()?->name,
                ],
            );

            if ($accessChanged) {
                AccessInvariants::assertManagementCapacity('web');
            }
        });

        return back()->with('success', __('user_updated'));
    }

    /**
     * Delete team member from tenant.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', __('cannot_delete_own_account'));
        }

        if (! $this->policy->canManage($request->user(), $user, 'web')) {
            return back()->with('error', __('cannot_manage_more_privileged_user'));
        }

        DB::transaction(function () use ($user, $request) {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->roles->first()?->name,
            ];
            $targetId = $user->id;
            $targetLabel = $user->email;

            $this->userService->deleteUser($user);

            $this->audit->record(
                $request->user(), 'web', 'user.deleted', 'user', null, $targetLabel,
                before: $before + ['id' => $targetId],
            );

            AccessInvariants::assertManagementCapacity('web');
        });

        return back()->with('success', __('user_removed'));
    }
}
