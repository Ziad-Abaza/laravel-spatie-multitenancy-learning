<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Services\AccessInvariants;
use Modules\Access\Services\AuditWriter;
use Modules\Access\Services\ManagementPolicy;
use Modules\Landlord\Models\LandlordUser;

/**
 * Platform administrator management. Every mutating endpoint is gated by an
 * admins.* permission (route middleware) AND by ManagementPolicy scope rules
 * — an actor can never touch a principal holding capabilities they lack.
 * Mutations run in transactions with post-write invariant checks.
 */
class LandlordAdminController extends Controller
{
    public function __construct(
        protected ManagementPolicy $policy,
        protected AuditWriter $audit
    ) {}

    public function index(Request $request): Response
    {
        $actor = $request->user('landlord');

        $admins = LandlordUser::with('roles')
            ->latest()
            ->get()
            ->map(fn (LandlordUser $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => $admin->status ?? 'active',
                'roles' => $admin->roles->pluck('name'),
                'created_at' => $admin->created_at?->format('Y-m-d') ?? '-',
                'can_edit' => $this->policy->canManage($actor, $admin, 'landlord'),
                'can_delete' => $this->policy->isDeletableBy($actor, $admin, 'landlord'),
            ]);

        return Inertia::render('Landlord/Admins/Index', [
            'admins' => $admins,
            'assignableRoles' => $this->policy->assignableRoleNames($actor, 'landlord'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user('landlord');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(LandlordUser::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'landlord')],
        ]);

        $this->policy->assertRoleGrantable($actor, $validated['role'], 'landlord');

        $admin = DB::transaction(function () use ($validated) {
            $admin = LandlordUser::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);
            $admin->assignRole($validated['role']);

            return $admin;
        });

        $this->audit->record($actor, 'landlord', 'admin.created', 'admin_user', $admin, after: [
            'email' => $admin->email,
            'roles' => [$validated['role']],
        ]);

        return back()->with('success', __('admin_created'));
    }

    /**
     * Profile fields only — role, status, and password each have their own
     * explicitly gated endpoint; they are never mutable through update.
     */
    public function update(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');
        $this->policy->assertCanManage($actor, $admin, 'landlord');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(LandlordUser::class, 'email')->ignore($admin->id)],
        ]);

        $before = ['name' => $admin->name, 'email' => $admin->email];
        $admin->update($validated);

        $this->audit->record($actor, 'landlord', 'admin.updated', 'admin_user', $admin, before: $before, after: $validated);

        return back()->with('success', __('admin_updated'));
    }

    public function assignRole(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'landlord')],
        ]);

        $this->policy->assertNotSelfMutation($actor, $admin);
        $this->policy->assertAssignableRole($actor, $admin, $validated['role'], 'landlord');

        $before = $admin->roles->pluck('name')->all();

        DB::transaction(function () use ($admin, $validated) {
            $admin->syncRoles([$validated['role']]);
            AccessInvariants::assertManagementCapacity('landlord');
        });

        $this->audit->record($actor, 'landlord', 'admin.role_changed', 'admin_user', $admin, before: ['roles' => $before], after: ['roles' => [$validated['role']]]);

        return back()->with('success', __('admin_role_updated'));
    }

    public function resetPassword(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');
        $this->policy->assertCanManage($actor, $admin, 'landlord');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->update(['password' => Hash::make($validated['password'])]);

        $this->audit->record($actor, 'landlord', 'admin.password_reset', 'admin_user', $admin);

        return back()->with('success', __('admin_password_reset'));
    }

    public function suspend(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');
        $this->policy->assertNotSelfMutation($actor, $admin);
        $this->policy->assertCanManage($actor, $admin, 'landlord');

        DB::transaction(function () use ($admin) {
            $admin->update(['status' => 'suspended']);
            AccessInvariants::assertManagementCapacity('landlord');
        });

        $this->audit->record($actor, 'landlord', 'admin.suspended', 'admin_user', $admin);

        return back()->with('success', __('admin_suspended'));
    }

    public function reactivate(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');
        $this->policy->assertCanManage($actor, $admin, 'landlord');

        $admin->update(['status' => 'active']);

        $this->audit->record($actor, 'landlord', 'admin.reactivated', 'admin_user', $admin);

        return back()->with('success', __('admin_reactivated'));
    }

    public function destroy(Request $request, LandlordUser $admin): RedirectResponse
    {
        $actor = $request->user('landlord');
        $this->policy->assertNotSelfMutation($actor, $admin);
        $this->policy->assertCanManage($actor, $admin, 'landlord');

        $snapshot = ['email' => $admin->email, 'roles' => $admin->roles->pluck('name')->all()];

        DB::transaction(function () use ($admin) {
            $admin->roles()->detach();
            $admin->delete();
            AccessInvariants::assertManagementCapacity('landlord');
        });

        $this->audit->record($actor, 'landlord', 'admin.deleted', 'admin_user', null, $admin->email, before: $snapshot);

        return back()->with('success', __('admin_deleted'));
    }
}
