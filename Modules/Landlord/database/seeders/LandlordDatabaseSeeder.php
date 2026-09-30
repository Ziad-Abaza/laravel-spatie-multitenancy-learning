<?php

namespace Modules\Landlord\Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\LandlordPermissions;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\LandlordUser;
use Modules\Subscription\Database\Seeders\PlanSeeder;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;

class LandlordDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed SaaS Subscription Plans
        $this->call(PlanSeeder::class);

        // 2. Setup Landlord permission catalog + Super Admin role and user —
        //    never with a predictable password outside local development.
        //    Set SEED_LANDLORD_ADMIN_PASSWORD to control the credential.
        app(AccessBaselineProvisioner::class)->ensureLandlordBaseline();

        $adminPassword = env('SEED_LANDLORD_ADMIN_PASSWORD')
            ?? (app()->environment('local') ? 'password' : null);

        if ($adminPassword !== null) {
            $admin = LandlordUser::updateOrCreate(
                ['email' => 'admin@landlord.test'],
                [
                    'name' => 'Landlord Super Admin',
                    'password' => Hash::make($adminPassword),
                ]
            );

            if (method_exists($admin, 'assignRole')) {
                $admin->assignRole(LandlordPermissions::ROLE_SUPER_ADMIN);
            }
        }

        // 3. Seed Default System Settings on Landlord connection
        $defaultSettings = [
            ['branding', 'app_name', 'SaaS Enterprise'],
            ['branding', 'tagline', 'Multi-Tenant Modular Enterprise Architecture'],
            ['theme', 'palette', 'indigo'],
            ['theme', 'mode', 'dark'],
            ['localization', 'default_locale', 'en'],
            ['localization', 'supported_locales', ['en', 'ar']],
            ['system', 'allow_registration', true],
        ];

        $settings = app(SettingManagerContract::class);
        foreach ($defaultSettings as [$domain, $key, $value]) {
            $settings->set($key, $value, $domain, true);
        }

        // 4. Update existing tenants with plans and subscriptions if needed
        $starterPlan = Plan::where('slug', 'starter')->first();
        $proPlan = Plan::where('slug', 'pro')->first();
        $subscriptionService = app(SubscriptionService::class);

        $suffix = config('multitenancy.tenant_domain_suffix') ?: 'localhost';

        $tenant1 = Tenant::where('slug', 'tenant1')->orWhere('domain', "tenant1.{$suffix}")->first();
        if ($tenant1) {
            $tenant1->update([
                'slug' => 'tenant1',
                'status' => TenantStatus::Active,
                'plan_id' => $proPlan?->id ?? $starterPlan?->id,
            ]);
            if (! $tenant1->currentSubscription && $proPlan) {
                $subscriptionService->subscribeTenant($tenant1, $proPlan, 'monthly', false);
            }
        }

        $tenant2 = Tenant::where('slug', 'tenant2')->orWhere('domain', "tenant2.{$suffix}")->first();
        if ($tenant2) {
            $tenant2->update([
                'slug' => 'tenant2',
                'status' => TenantStatus::Active,
                'plan_id' => $starterPlan?->id,
            ]);
            if (! $tenant2->currentSubscription && $starterPlan) {
                $subscriptionService->subscribeTenant($tenant2, $starterPlan, 'monthly', false);
            }
        }
    }
}
