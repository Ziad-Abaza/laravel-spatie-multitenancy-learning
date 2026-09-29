<?php

namespace Modules\Landlord\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\LandlordUser;
use Modules\Landlord\Models\Tenant;
use Modules\Settings\Models\Setting;
use Modules\Subscription\Database\Seeders\PlanSeeder;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;
use Spatie\Permission\Models\Role;

class LandlordDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed SaaS Subscription Plans
        $this->call(PlanSeeder::class);

        // 2. Setup Landlord Super Admin role and user
        try {
            $role = Role::findOrCreate('Super Admin', 'landlord');
        } catch (\Throwable) {
            // Guard role creation if already exists
        }

        $admin = LandlordUser::updateOrCreate(
            ['email' => 'admin@landlord.test'],
            [
                'name' => 'Landlord Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        if (method_exists($admin, 'assignRole')) {
            try {
                $admin->assignRole('Super Admin');
            } catch (\Throwable) {
            }
        }

        // 3. Seed Default System Settings on Landlord connection
        $defaultSettings = [
            ['domain' => 'branding', 'key' => 'app_name', 'value' => 'SaaS Enterprise', 'type' => 'string', 'is_public' => true],
            ['domain' => 'branding', 'key' => 'tagline', 'value' => 'Multi-Tenant Modular Enterprise Architecture', 'type' => 'string', 'is_public' => true],
            ['domain' => 'theme', 'key' => 'default_theme', 'value' => 'indigo', 'type' => 'string', 'is_public' => true],
            ['domain' => 'theme', 'key' => 'default_mode', 'value' => 'dark', 'type' => 'string', 'is_public' => true],
            ['domain' => 'localization', 'key' => 'default_locale', 'value' => 'en', 'type' => 'string', 'is_public' => true],
            ['domain' => 'localization', 'key' => 'supported_locales', 'value' => json_encode(['en', 'ar']), 'type' => 'json', 'is_public' => true],
            ['domain' => 'system', 'key' => 'registration_enabled', 'value' => '1', 'type' => 'boolean', 'is_public' => true],
            ['domain' => 'system', 'key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'is_public' => false],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // 4. Update existing tenants with plans and subscriptions if needed
        $starterPlan = Plan::where('slug', 'starter')->first();
        $proPlan = Plan::where('slug', 'pro')->first();
        $subscriptionService = app(SubscriptionService::class);

        $tenant1 = Tenant::where('slug', 'tenant1')->orWhere('domain', 'tenant1.localhost')->first();
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

        $tenant2 = Tenant::where('slug', 'tenant2')->orWhere('domain', 'tenant2.localhost')->first();
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
