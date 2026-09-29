<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Access\Services\TenantUserService;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Subscription\Models\Plan;
use Tests\TestCase;

class QuotaEnforcementTest extends TestCase
{
    public function test_quota_service_returns_plan_limits(): void
    {
        $quotaManager = app(QuotaManagerContract::class);

        $tenant1 = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant1);

        $limit1 = $quotaManager->getUserLimit($tenant1);
        $this->assertIsInt($limit1);
        $this->assertGreaterThan(0, $limit1);
    }

    public function test_tenant_user_service_blocks_creation_when_quota_is_reached(): void
    {
        $tenant = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant);

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
}
