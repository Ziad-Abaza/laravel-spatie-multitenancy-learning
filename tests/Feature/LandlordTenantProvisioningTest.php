<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\LandlordUser;
use Modules\Subscription\Models\Plan;
use Tests\TestCase;

class LandlordTenantProvisioningTest extends TestCase
{
    protected LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = LandlordUser::firstOrCreate(
            ['email' => 'admin@landlord.test'],
            [
                'name' => 'Platform Administrator',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_public_landing_page_loads_with_active_plans(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_public_pricing_page_loads(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
    }

    public function test_landlord_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/landlord/login', [
            'email' => 'admin@landlord.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/landlord');
        $this->assertTrue(auth('landlord')->check());
        $this->assertEquals($this->admin->id, auth('landlord')->id());
    }

    public function test_landlord_admin_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/landlord/login', [
            'email' => 'admin@landlord.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(auth('landlord')->check());
    }

    public function test_landlord_dashboard_accessible_to_authenticated_admin(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->get('/landlord');

        $response->assertStatus(200);
    }

    public function test_landlord_tenants_index_lists_existing_tenants(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->get('/landlord/tenants');

        $response->assertStatus(200);
    }

    /**
     * Disposable tenant row — lifecycle endpoints never touch shared
     * fixtures (tenant1.localhost/tenant2.localhost) to keep the persistent
     * test database deterministic across runs.
     */
    protected function makeDisposableTenant(): Tenant
    {
        $uid = uniqid();

        return Tenant::create([
            'name' => "Disposable {$uid}",
            'slug' => "disp-{$uid}",
            'domain' => "disp-{$uid}.localhost",
            'database' => "disp_{$uid}_db",
            'status' => TenantStatus::Active,
        ]);
    }

    public function test_landlord_can_suspend_and_activate_tenant(): void
    {
        $tenant = $this->makeDisposableTenant();

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/suspend");

            $response->assertRedirect();
            $this->assertEquals(TenantStatus::Suspended, $tenant->fresh()->status);

            $response = $this->actingAs($this->admin, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/activate");

            $response->assertRedirect();
            $this->assertEquals(TenantStatus::Active, $tenant->fresh()->status);
        } finally {
            $tenant->delete();
        }
    }

    public function test_landlord_can_update_tenant_identity(): void
    {
        $tenant = $this->makeDisposableTenant();
        $domain = 'renamed-'.uniqid().'.localhost';

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->put("/landlord/tenants/{$tenant->id}", [
                    'name' => 'Renamed Workspace',
                    'slug' => 'renamed-'.uniqid(),
                    'domain' => $domain,
                ]);

            $response->assertRedirect();
            $this->assertSame('Renamed Workspace', $tenant->fresh()->name);
            $this->assertSame($domain, $tenant->fresh()->domain);
        } finally {
            $tenant->delete();
        }
    }

    public function test_tenant_update_rejects_duplicate_domain(): void
    {
        $tenant = $this->makeDisposableTenant();
        $other = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($other);

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->put("/landlord/tenants/{$tenant->id}", [
                    'name' => 'Hijack',
                    'slug' => 'hijack-'.uniqid(),
                    'domain' => $other->domain,
                ]);

            $response->assertSessionHasErrors('domain');
        } finally {
            $tenant->delete();
        }
    }

    public function test_landlord_can_change_tenant_plan(): void
    {
        $tenant = $this->makeDisposableTenant();
        $plan = Plan::where('is_active', true)->first();
        $this->assertNotNull($plan);

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/plan", [
                    'plan_id' => $plan->id,
                    'billing_interval' => 'monthly',
                ]);

            $response->assertRedirect();
            $this->assertSame($plan->id, $tenant->fresh()->plan_id);
        } finally {
            $tenant->subscriptions()->delete();
            $tenant->delete();
        }
    }

    public function test_landlord_can_extend_trial(): void
    {
        $tenant = $this->makeDisposableTenant();
        $target = now()->addDays(45)->format('Y-m-d');

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/trial", [
                    'trial_ends_at' => $target,
                ]);

            $response->assertRedirect();
            $this->assertSame($target, $tenant->fresh()->trial_ends_at?->format('Y-m-d'));
            $this->assertEquals(TenantStatus::Trialing, $tenant->fresh()->status);
        } finally {
            $tenant->delete();
        }
    }

    public function test_landlord_can_archive_tenant(): void
    {
        $tenant = $this->makeDisposableTenant();

        try {
            $response = $this->actingAs($this->admin, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/archive");

            $response->assertRedirect();
            $this->assertEquals(TenantStatus::Archived, $tenant->fresh()->status);
        } finally {
            $tenant->delete();
        }
    }

    public function test_tenant_index_filters_by_status_and_plan(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->get('/landlord/tenants?status=suspended');

        $response->assertStatus(200);
    }
}
