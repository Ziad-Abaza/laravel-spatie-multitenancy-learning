<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Modules\Access\Models\AuditLog;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Enums\UserStatus;
use Modules\Landlord\Services\TenantLifecycleService;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    protected function verifiedMember(Tenant $tenant, array $attrs = []): User
    {
        return $tenant->execute(fn () => User::create(array_merge([
            'name' => 'Audit Member',
            'email' => 'audit-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
        ], $attrs)));
    }

    public function test_login_and_logout_are_recorded_in_tenant_audit_log(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->verifiedMember($tenant);

        try {
            $this->post("http://{$tenant->domain}/login", [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect();

            $tenant->execute(function () use ($user) {
                $log = AuditLog::where('action', 'auth.login')->where('actor_id', $user->id)->latest('id')->first();
                $this->assertNotNull($log);
                $this->assertSame('web', $log->actor_guard);
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_failed_login_is_recorded_without_password(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->verifiedMember($tenant);

        try {
            $this->post("http://{$tenant->domain}/login", [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $tenant->execute(function () use ($user) {
                $log = AuditLog::where('action', 'auth.login_failed')->latest('id')->first();
                $this->assertNotNull($log);
                $this->assertStringContainsString($user->email, (string) $log->target_label);
                $this->assertStringNotContainsString('wrong-password', (string) json_encode($log->before));
                $this->assertStringNotContainsString('wrong-password', (string) json_encode($log->after));
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_user_mutations_are_audited_with_redacted_passwords(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $admin = $tenant->execute(function () {
                app(AccessBaselineProvisioner::class)->ensureBaseline();
                $admin = User::orderBy('id')->firstOrFail();
                $admin->markEmailAsVerified();

                return $admin;
            });

            $this->actingAs($admin, 'web')
                ->post("http://{$tenant->domain}/users", [
                    'name' => 'Audited Member',
                    'email' => 'audited-'.uniqid().'@test.local',
                    'password' => 'password123',
                    'role' => TenantPermissions::ROLE_MEMBER,
                ])->assertRedirect();

            $tenant->execute(function () {
                $log = AuditLog::where('action', 'user.created')->latest('id')->first();
                $this->assertNotNull($log);
                $this->assertStringNotContainsString('password123', (string) json_encode($log->after));
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_audit_viewer_is_permission_gated(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $member = $this->verifiedMember($tenant);

        try {
            $tenant->execute(fn () => app(AccessBaselineProvisioner::class)->ensureBaseline());

            // Member without audit.view → 403.
            $this->actingAs($member, 'web')
                ->get("http://{$tenant->domain}/audit-logs")
                ->assertForbidden();

            $tenant->execute(function () use ($member) {
                $member->assignRole(TenantPermissions::ROLE_ADMIN);
            });

            $this->actingAs($member, 'web')
                ->get("http://{$tenant->domain}/audit-logs")
                ->assertOk();
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_revoke_other_sessions_requires_password_and_audits(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->verifiedMember($tenant);

        try {
            $this->actingAs($user, 'web')
                ->post("http://{$tenant->domain}/profile/revoke-sessions", [
                    'current_password' => 'wrong',
                ])->assertSessionHasErrors('current_password');

            $this->actingAs($user, 'web')
                ->post("http://{$tenant->domain}/profile/revoke-sessions", [
                    'current_password' => 'password',
                ])->assertRedirect()
                ->assertSessionHas('success');

            $tenant->execute(function () {
                $this->assertNotNull(
                    AuditLog::where('action', 'auth.sessions_revoked')->latest('id')->first()
                );
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_landlord_lifecycle_events_are_recorded_in_admin_audit_log(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            app(TenantLifecycleService::class)->suspend($tenant, 'audit-check');

            $this->assertDatabaseHas('admin_audit_logs', [
                'action' => 'tenant.status_changed',
                'target_kind' => 'tenant',
                'target_id' => $tenant->id,
            ]);
        } finally {
            Tenant::forgetCurrent();
        }
    }
}
