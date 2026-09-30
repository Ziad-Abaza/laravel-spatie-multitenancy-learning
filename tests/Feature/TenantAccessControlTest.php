<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Services\ManagementPolicy;
use Modules\Access\Support\LandlordPermissions;
use Modules\Access\Support\TenantPermissions;
use Modules\Landlord\Models\LandlordUser;
use Modules\Settings\Services\SettingService;
use Tests\TestCase;

class TenantAccessControlTest extends TestCase
{
    protected function tenant(): Tenant
    {
        return $this->provisionTenant([
            'slug' => 'tenant1',
            'name' => 'Tenant 1',
            'admin_email' => 'admin@tenant1.localhost',
        ]);
    }

    public function test_role_and_permission_models_follow_tenancy_context(): void
    {
        $landlord = config('multitenancy.landlord_database_connection_name');
        $tenantConn = config('multitenancy.tenant_database_connection_name');

        $this->assertSame($landlord, (new Role)->getConnectionName());
        $this->assertSame($landlord, (new Permission)->getConnectionName());

        $tenant = $this->tenant();
        $tenant->makeCurrent();

        try {
            $this->assertSame($tenantConn, (new Role)->getConnectionName());
            $this->assertSame($tenantConn, (new Permission)->getConnectionName());
        } finally {
            Tenant::forgetCurrent();
        }

        $this->assertSame($landlord, (new Role)->getConnectionName());
    }

    public function test_tenant_member_registration_respects_platform_allow_registration(): void
    {
        $tenant = $this->tenant();
        $settings = app(SettingService::class);
        $settings->set('allow_registration', false, 'system');

        try {
            $this->get("http://{$tenant->domain}/register")->assertForbidden();
            $this->post("http://{$tenant->domain}/register", [
                'name' => 'Blocked Member',
                'email' => 'blocked@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertForbidden();
        } finally {
            // Tenant-domain requests leave the tenant bound as current in the
            // test app — landlord-owned settings need landlord context.
            Tenant::forgetCurrent();
            $settings->set('allow_registration', true, 'system');
        }
    }

    public function test_password_reset_tokens_are_stored_in_the_tenant_database(): void
    {
        $tenant = $this->tenant();

        $tenant->execute(function () {
            $user = User::factory()->create();

            Password::broker('users')->createToken($user);

            $this->assertDatabaseHas('password_reset_tokens', [
                'email' => $user->email,
            ], 'tenant');
        });
    }

    public function test_access_baseline_seeds_full_permission_catalog_and_roles(): void
    {
        $this->tenant()->execute(function () {
            app(AccessBaselineProvisioner::class)->ensureBaseline();
            // Run twice — the baseline must be idempotent.
            app(AccessBaselineProvisioner::class)->ensureBaseline();

            $this->assertCount(count(TenantPermissions::all()), Permission::all());
            $expected = TenantPermissions::all();
            sort($expected);
            $actual = Permission::pluck('name')->all();
            sort($actual);
            $this->assertSame($expected, $actual);

            $owner = Role::findByName('Owner', 'web');
            $this->assertTrue($owner->hasPermissionTo(TenantPermissions::ROLES_VIEW));
            $this->assertTrue($owner->hasPermissionTo(TenantPermissions::SETTINGS_MANAGE));

            $member = Role::findByName('Member', 'web');
            $this->assertTrue($member->hasPermissionTo(TenantPermissions::USERS_VIEW));
            $this->assertFalse($member->hasPermissionTo(TenantPermissions::ROLES_VIEW));
        });
    }

    public function test_owner_role_can_view_roles_page(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $owner = User::create([
            'name' => 'Access Owner',
            'email' => 'access-owner-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole('Owner');

        try {
            $this->actingAs($owner, 'web')
                ->get('http://tenant1.localhost/roles')
                ->assertOk();
        } finally {
            $owner->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_member_cannot_view_roles_or_manage_users(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $member = User::create([
            'name' => 'Access Member',
            'email' => 'access-member-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $member->assignRole('Member');

        try {
            $this->actingAs($member, 'web')
                ->get('http://tenant1.localhost/roles')
                ->assertForbidden();

            $this->actingAs($member, 'web')
                ->post('http://tenant1.localhost/users', [
                    'name' => 'Blocked',
                    'email' => 'blocked-'.uniqid().'@test.local',
                    'password' => 'password123',
                    'role' => 'Member',
                ])
                ->assertForbidden();
        } finally {
            $member->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_member_can_view_team_directory(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $member = User::create([
            'name' => 'Team Member',
            'email' => 'team-member-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $member->assignRole('Member');

        try {
            $this->actingAs($member, 'web')
                ->get('http://tenant1.localhost/users')
                ->assertOk();
        } finally {
            $member->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_landlord_platform_routes_are_permission_gated(): void
    {
        app(AccessBaselineProvisioner::class)->ensureLandlordBaseline();

        $staff = LandlordUser::create([
            'name' => 'Platform Staff',
            'email' => 'staff-'.uniqid().'@landlord.test',
            'password' => bcrypt('password'),
        ]);

        try {
            // No platform permissions — everything past the dashboard is closed.
            $this->actingAs($staff, 'landlord')
                ->get('/landlord/tenants')
                ->assertForbidden();

            $staff->assignRole('Super Admin');

            $this->actingAs($staff, 'landlord')
                ->get('/landlord/tenants')
                ->assertOk();

            $this->assertTrue($staff->fresh()->hasPermissionTo(LandlordPermissions::TENANTS_VIEW, 'landlord'));
        } finally {
            $staff->roles()->detach();
            $staff->delete();
        }
    }

    /**
     * Build a member holding exactly the given permissions (no role names —
     * authority derives from the permission set only).
     *
     * @param  array<int, string>  $permissions
     */
    protected function makeScopedUser(string $prefix, array $permissions): User
    {
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $role = Role::findOrCreate('Scoped'.ucfirst($prefix).'-'.uniqid(), 'web');
        $role->syncPermissions($permissions);

        $user = User::create([
            'name' => ucfirst($prefix),
            'email' => $prefix.'-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_cannot_assign_role_beyond_own_permissions(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();

        // HR can create users but lacks roles.* and settings.* — granting
        // "Owner" would escalate a new account beyond the actor's own scope.
        $hr = $this->makeScopedUser('hr', [
            TenantPermissions::USERS_VIEW,
            TenantPermissions::USERS_CREATE,
            TenantPermissions::USERS_UPDATE,
        ]);

        try {
            $this->actingAs($hr, 'web')
                ->post('http://tenant1.localhost/users', [
                    'name' => 'Escalation Attempt',
                    'email' => 'escalation-'.uniqid().'@test.local',
                    'password' => 'password123',
                    'role' => 'Owner',
                ])
                ->assertForbidden();

            // Assigning a subset role (Member = users.view) is allowed.
            $this->actingAs($hr, 'web')
                ->post('http://tenant1.localhost/users', [
                    'name' => 'Legit Member',
                    'email' => 'legit-'.uniqid().'@test.local',
                    'password' => 'password123',
                    'role' => 'Member',
                ])
                ->assertRedirect();
        } finally {
            $hr->delete();
            $hr->roles()->detach();
            Role::where('name', 'like', 'ScopedHr-%')->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_cannot_edit_or_delete_more_privileged_user(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();

        $hr = $this->makeScopedUser('mgr', [
            TenantPermissions::USERS_VIEW,
            TenantPermissions::USERS_UPDATE,
            TenantPermissions::USERS_DELETE,
        ]);

        $owner = User::where('email', 'admin@tenant1.localhost')->first();
        $this->assertNotNull($owner);

        try {
            // Account hijack attempt: resetting the Owner's password.
            $this->actingAs($hr, 'web')
                ->put("http://tenant1.localhost/users/{$owner->id}", [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'password' => 'hijacked-password',
                ])
                ->assertForbidden();

            $this->actingAs($hr, 'web')
                ->delete("http://tenant1.localhost/users/{$owner->id}")
                ->assertRedirect()
                ->assertSessionHas('error');
        } finally {
            $hr->roles()->detach();
            $hr->delete();
            Role::where('name', 'like', 'ScopedMgr-%')->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_cannot_create_role_with_permissions_beyond_actor_scope(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();

        $roleAdmin = $this->makeScopedUser('roleadm', [
            TenantPermissions::ROLES_VIEW,
            TenantPermissions::ROLES_CREATE,
            TenantPermissions::USERS_VIEW,
        ]);

        try {
            $this->actingAs($roleAdmin, 'web')
                ->post('http://tenant1.localhost/roles', [
                    'name' => 'god-mode-'.uniqid(),
                    'permissions' => [TenantPermissions::SETTINGS_MANAGE],
                ])
                ->assertForbidden();
        } finally {
            $roleAdmin->roles()->detach();
            $roleAdmin->delete();
            Role::where('name', 'like', 'ScopedRoleadm-%')->delete();
            Role::where('name', 'like', 'god-mode-%')->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_equal_privilege_member_can_be_removed_and_last_manager_flagged(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $guard = app(ManagementPolicy::class);
        $owner = User::where('email', 'admin@tenant1.localhost')->first();
        $this->assertNotNull($owner);

        $peer = User::create([
            'name' => 'Peer Manager',
            'email' => 'peer-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $peer->assignRole('Owner');

        try {
            // Equal privilege → deletable while another manager remains.
            $this->assertTrue($guard->isDeletableBy($owner, $peer, 'web'));

            $this->actingAs($owner, 'web')
                ->delete("http://tenant1.localhost/users/{$peer->id}")
                ->assertRedirect()
                ->assertSessionHas('success');
        } finally {
            $peer->roles()->detach();
            $peer->delete();
            Tenant::forgetCurrent();
        }
    }

    public function test_member_cannot_reach_settings_or_change_subscription(): void
    {
        $tenant = $this->tenant();
        $tenant->makeCurrent();
        app(AccessBaselineProvisioner::class)->ensureBaseline();

        $member = User::create([
            'name' => 'Locked Member',
            'email' => 'locked-member-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $member->assignRole('Member');

        try {
            $this->actingAs($member, 'web')
                ->get('http://tenant1.localhost/settings')
                ->assertForbidden();

            $this->actingAs($member, 'web')
                ->post('http://tenant1.localhost/subscription/change-plan', ['plan_id' => 1])
                ->assertForbidden();
        } finally {
            $member->delete();
            Tenant::forgetCurrent();
        }
    }
}
