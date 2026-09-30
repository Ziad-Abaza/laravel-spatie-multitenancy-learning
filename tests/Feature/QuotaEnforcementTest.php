<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Access\Services\TenantUserService;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Landlord\Services\LandlordMetricsService;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class QuotaEnforcementTest extends TestCase
{
    public function test_quota_service_returns_plan_limits(): void
    {
        $quotaManager = app(QuotaManagerContract::class);

        $tenant1 = $this->provisionTenant(['slug' => 'tenant1']);

        $limit1 = $quotaManager->getUserLimit($tenant1);
        $this->assertIsInt($limit1);
        $this->assertGreaterThan(0, $limit1);
    }

    public function test_tenant_user_service_blocks_creation_when_quota_is_reached(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        $tenant->makeCurrent();

        // Create a restricted test plan with max_users = 1
        $restrictedPlan = Plan::firstOrCreate(
            ['slug' => 'test-micro-plan'],
            [
                'name' => ['en' => 'Micro', 'ar' => 'مايكرو'],
                'price' => 5,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'limits' => [
                    'max_users' => 1,
                    'max_storage_mb' => 500,
                ],
            ]
        );

        $originalPlanId = $tenant->plan_id;
        $tenant->update(['plan_id' => $restrictedPlan->id]);
        $tenant->refresh();

        $userService = app(TenantUserService::class);

        // Ensure 1 user exists in tenant
        User::firstOrCreate(
            ['email' => 'existing_quota_user@example.com'],
            ['name' => 'Existing User', 'password' => bcrypt('password')]
        );

        // Attempting to create another user MUST throw ValidationException
        $this->expectException(ValidationException::class);

        try {
            $userService->createUser([
                'name' => 'Quota Exceeded User',
                'email' => 'exceeded_quota_user@example.com',
                'password' => 'secret1234',
                'role' => 'Member',
            ], $tenant);
        } finally {
            // Restore original plan
            $tenant->update(['plan_id' => $originalPlanId]);
            Tenant::forgetCurrent();
        }
    }

    public function test_subscription_amount_and_interval_come_from_the_plan(): void
    {
        $yearlyPlan = Plan::create([
            'name' => ['en' => 'Annual', 'ar' => 'سنوي'],
            'slug' => 'annual-'.uniqid(),
            'description' => ['en' => '', 'ar' => ''],
            'price' => 480,
            'currency' => 'USD',
            'billing_interval' => 'yearly',
            'trial_days' => 0,
            'is_active' => true,
            'limits' => ['max_users' => 10, 'max_storage_mb' => 1000],
            'sort_order' => 50,
        ]);

        $subscription = app(SubscriptionService::class)
            ->subscribeTenant($this->createTenantRecord(), $yearlyPlan, false);

        $this->assertSame('yearly', $subscription->billing_interval);
        $this->assertSame(480.0, (float) $subscription->amount);
        $this->assertSame('USD', $subscription->currency);
        $this->assertTrue($subscription->ends_at->gt(now()->addMonths(11)));
    }

    public function test_change_plan_refreshes_interval_currency_and_ends_at(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'billing-swap']);
        $yearlyPlan = Plan::create([
            'name' => ['en' => 'Annual', 'ar' => 'سنوي'],
            'slug' => 'annual-'.uniqid(),
            'description' => ['en' => '', 'ar' => ''],
            'price' => 480,
            'currency' => 'USD',
            'billing_interval' => 'yearly',
            'trial_days' => 0,
            'is_active' => true,
            'limits' => ['max_users' => 10, 'max_storage_mb' => 1000],
            'sort_order' => 50,
        ]);

        $subscription = app(SubscriptionService::class)->changePlan($tenant, $yearlyPlan);

        $this->assertSame($yearlyPlan->id, $subscription->plan_id);
        $this->assertSame('yearly', $subscription->billing_interval);
        $this->assertSame(480.0, (float) $subscription->amount);
        $this->assertTrue($subscription->ends_at->gt(now()->addMonths(11)));
    }

    public function test_metrics_emit_real_mrr_key_with_sql_aggregation(): void
    {
        $monthly = $this->provisionTenant(['slug' => 'mrr-monthly']);
        $yearlyPlan = Plan::create([
            'name' => ['en' => 'Annual', 'ar' => 'سنوي'],
            'slug' => 'annual-'.uniqid(),
            'price' => 1200,
            'currency' => 'USD',
            'billing_interval' => 'yearly',
            'is_active' => true,
            'trial_days' => 0,
            'limits' => ['max_users' => 10],
        ]);
        $yearly = $this->provisionTenant(['slug' => 'mrr-yearly']);

        $service = app(SubscriptionService::class);
        $service->changePlan($monthly, Plan::where('slug', 'pro')->first()); // $29 monthly
        $service->changePlan($yearly, $yearlyPlan); // $1200/yr → $100/mo

        $metrics = app(LandlordMetricsService::class)->getMetrics();

        $this->assertArrayHasKey('mrr', $metrics);
        $this->assertSame(129.0, $metrics['mrr']); // 29 + 1200/12
    }

    public function test_storage_usage_reflects_real_tenant_media_bytes(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'storage-usage']);

        $tenant->execute(function () {
            Media::on('tenant')->create([
                'model_type' => User::class,
                'model_id' => 1,
                'uuid' => (string) Str::uuid(),
                'collection_name' => 'avatars',
                'name' => 'avatar.png',
                'file_name' => 'avatar.png',
                'mime_type' => 'image/png',
                'disk' => 'public',
                'size' => 2097152, // 2 MB
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);
        });

        $this->assertSame(2, app(QuotaManagerContract::class)->getStorageUsageMb($tenant));
    }
}
