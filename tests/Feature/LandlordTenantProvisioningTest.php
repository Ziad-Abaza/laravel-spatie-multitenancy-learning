<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Modules\Access\Support\LandlordPermissions;
use Modules\Core\Enums\SubscriptionStatus;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\LandlordUser;
use Modules\Landlord\Services\TenantLifecycleService;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\Subscription;
use Tests\TestCase;

class LandlordTenantProvisioningTest extends TestCase
{
    protected LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = LandlordUser::create(
            [
                'email' => 'admin@landlord.test',
                'name' => 'Platform Administrator',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->admin->assignRole(LandlordPermissions::ROLE_SUPER_ADMIN);
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
     * Disposable landlord-side tenant row — lifecycle endpoints only touch
     * the landlord record, so no tenant database file is needed.
     */
    protected function makeDisposableTenant(): Tenant
    {
        $uid = uniqid();

        return $this->createTenantRecord([
            'name' => "Disposable {$uid}",
            'slug' => "disp-{$uid}",
            'domain' => "disp-{$uid}.localhost",
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
        $other = $this->createTenantRecord();

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

    public function test_plan_with_attached_tenant_cannot_be_deleted(): void
    {
        $plan = Plan::where('slug', 'starter')->first();
        $this->assertNotNull($plan);

        $this->createTenantRecord(['plan_id' => $plan->id]);

        $this->actingAs($this->admin, 'landlord')
            ->delete("/landlord/plans/{$plan->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotNull($plan->fresh());
    }

    public function test_plan_with_subscriptions_cannot_be_deleted(): void
    {
        $tenant = $this->provisionTenant(['plan_id' => Plan::where('slug', 'pro')->first()?->id]);
        $plan = $tenant->plan;

        $this->actingAs($this->admin, 'landlord')
            ->delete("/landlord/plans/{$plan->id}")
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_extend_trial_rejects_non_trialing_tenants(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'trial-guard']);
        app(TenantLifecycleService::class)->suspend($tenant);

        $this->actingAs($this->admin, 'landlord')
            ->post("/landlord/tenants/{$tenant->id}/trial", [
                'trial_ends_at' => now()->addDays(30)->toDateString(),
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(TenantStatus::Suspended->value, $tenant->fresh()->getStatus());
    }

    public function test_archived_tenant_is_terminal(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'terminal']);
        app(TenantLifecycleService::class)->archive($tenant);

        $this->actingAs($this->admin, 'landlord')
            ->post("/landlord/tenants/{$tenant->id}/activate")
            ->assertSessionHasErrors('status');

        $this->assertSame(TenantStatus::Archived->value, $tenant->fresh()->getStatus());
    }

    public function test_orphan_plan_can_be_deleted(): void
    {
        $plan = Plan::create([
            'name' => ['en' => 'Disposable', 'ar' => 'للاستعمال مرة واحدة'],
            'slug' => 'disposable-'.uniqid(),
            'description' => ['en' => '', 'ar' => ''],
            'price' => 10,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'trial_days' => 0,
            'limits' => ['max_users' => 1, 'max_storage_mb' => 100],
            'sort_order' => 99,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->delete("/landlord/plans/{$plan->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($plan->fresh());
    }

    public function test_expired_trial_tenant_is_suspended_by_lifecycle_command(): void
    {
        $tenant = $this->createTenantRecord([
            'slug' => 'trial-expired',
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->subDay(),
        ]);

        Artisan::call('tenants:enforce-lifecycle');

        $tenant->refresh();
        $this->assertSame(TenantStatus::Suspended, $tenant->status);
        $this->assertNotNull($tenant->suspended_at);
        $this->assertSame('Trial period expired', $tenant->settings['suspension_reason'] ?? null);
    }

    public function test_active_trial_is_not_touched_by_lifecycle_command(): void
    {
        $tenant = $this->createTenantRecord([
            'slug' => 'trial-active',
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays(7),
        ]);

        Artisan::call('tenants:enforce-lifecycle');

        $this->assertSame(TenantStatus::Trialing, $tenant->fresh()->status);
    }

    public function test_expired_subscription_marks_expired_and_suspends_tenant(): void
    {
        $tenant = $this->createTenantRecord([
            'slug' => 'sub-expired',
            'status' => TenantStatus::Active,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => Plan::first()->id,
            'status' => SubscriptionStatus::Active,
            'billing_interval' => 'monthly',
            'amount' => 10,
            'currency' => 'USD',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        Artisan::call('tenants:enforce-lifecycle');

        $this->assertSame(SubscriptionStatus::Expired, $subscription->fresh()->status);
        $this->assertSame(TenantStatus::Suspended, $tenant->fresh()->status);
    }

    public function test_archived_tenant_subscription_expires_without_touching_tenant(): void
    {
        $tenant = $this->createTenantRecord([
            'slug' => 'archived-expired',
            'status' => TenantStatus::Archived,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => Plan::first()->id,
            'status' => SubscriptionStatus::Active,
            'billing_interval' => 'monthly',
            'amount' => 10,
            'currency' => 'USD',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        Artisan::call('tenants:enforce-lifecycle');

        // Archived is terminal — the subscription still records expiry,
        // but the state machine must not resurrect or re-suspend it.
        $this->assertSame(SubscriptionStatus::Expired, $subscription->fresh()->status);
        $this->assertSame(TenantStatus::Archived, $tenant->fresh()->status);
    }

    public function test_open_ended_subscription_is_never_expired(): void
    {
        $tenant = $this->createTenantRecord([
            'slug' => 'sub-open',
            'status' => TenantStatus::Active,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => Plan::first()->id,
            'status' => SubscriptionStatus::Active,
            'billing_interval' => 'monthly',
            'amount' => 10,
            'currency' => 'USD',
            'starts_at' => now()->subMonths(2),
            'ends_at' => null,
        ]);

        Artisan::call('tenants:enforce-lifecycle');

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertSame(TenantStatus::Active, $tenant->fresh()->status);
    }
}
