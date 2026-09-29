<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Subscription\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => [
                    'en' => 'Starter',
                    'ar' => 'المبتدئ',
                ],
                'slug' => 'starter',
                'description' => [
                    'en' => 'Essential tools for small teams getting started',
                    'ar' => 'الأدوات الأساسية للفرق الصغيرة والناشئة',
                ],
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'trial_days' => 0,
                'limits' => [
                    'max_users' => 5,
                    'max_storage_mb' => 1024,
                    'features' => ['core_dashboard', 'team_access'],
                ],
                'sort_order' => 1,
            ],
            [
                'name' => [
                    'en' => 'Professional',
                    'ar' => 'المحترف',
                ],
                'slug' => 'pro',
                'description' => [
                    'en' => 'Advanced features and increased limits for growing companies',
                    'ar' => 'مميزات متقدمة وحصص أعلى للشركات سريعة النمو',
                ],
                'price' => 29.00,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'trial_days' => 14,
                'limits' => [
                    'max_users' => 25,
                    'max_storage_mb' => 10240,
                    'features' => ['core_dashboard', 'team_access', 'roles_customization', 'audit_logs', 'priority_support'],
                ],
                'sort_order' => 2,
            ],
            [
                'name' => [
                    'en' => 'Enterprise',
                    'ar' => 'المؤسسات',
                ],
                'slug' => 'enterprise',
                'description' => [
                    'en' => 'Unlimited scale, custom domain, and dedicated infrastructure',
                    'ar' => 'إمكانيات غير محدودة ودعم مخصص للشركات الكبرى',
                ],
                'price' => 99.00,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'trial_days' => 30,
                'limits' => [
                    'max_users' => 9999,
                    'max_storage_mb' => 102400,
                    'features' => ['*'],
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
