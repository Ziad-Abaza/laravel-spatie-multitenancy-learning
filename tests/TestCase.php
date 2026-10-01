<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Access\Models\Role;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Landlord\Models\LandlordUser;
use Modules\Subscription\Database\Seeders\PlanSeeder;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Absolute paths of tenant sqlite database files created during the test.
     *
     * @var list<string>
     */
    protected array $tenantDatabaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLandlordBaseline();
    }

    protected function tearDown(): void
    {
        if (Tenant::checkCurrent()) {
            Tenant::forgetCurrent();
        }

        DB::purge('tenant');

        foreach ($this->tenantDatabaseFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->tenantDatabaseFiles = [];

        parent::tearDown();
    }

    /**
     * Landlord-side fixture baseline the suite relies on: subscription plans,
     * the RBAC catalog, and governed system settings — the same rows the dev
     * seeders produce, without requiring a pre-provisioned dev database.
     */
    protected function seedLandlordBaseline(): void
    {
        $this->seed(PlanSeeder::class);
        app(AccessBaselineProvisioner::class)->ensureLandlordBaseline();

        $settings = app(SettingManagerContract::class);
        foreach ([
            ['branding', 'app_name', 'SaaS Enterprise'],
            ['branding', 'tagline', 'Multi-Tenant Modular Enterprise Architecture'],
            ['theme', 'palette', 'indigo'],
            ['theme', 'mode', 'dark'],
            ['localization', 'default_locale', 'en'],
            ['localization', 'supported_locales', ['en', 'ar']],
            ['system', 'allow_registration', true],
        ] as [$domain, $key, $value]) {
            $settings->set($key, $value, $domain);
        }
    }

    /**
     * Pre-migrated, pre-seeded tenant sqlite template — the expensive half of
     * provisioning (tenant migrations + permission catalog + baseline roles +
     * theme settings) is identical for every test tenant, so it is built once
     * per process and cloned by provisionTenant(). Mirrors the tenant-side
     * steps of TenantProvisioner::runTenantMigrations/seedTenantInitialData.
     */
    private static ?string $tenantTemplate = null;

    private function tenantTemplate(): string
    {
        if (self::$tenantTemplate === null || ! is_file(self::$tenantTemplate)) {
            self::$tenantTemplate = database_path('tenant_template_'.getmypid().'.sqlite');
            touch(self::$tenantTemplate);

            $scaffold = Tenant::create([
                'name' => '__template__',
                'slug' => '__tpl'.uniqid().'__',
                'domain' => '__tpl'.uniqid().'__.localhost',
                'database' => self::$tenantTemplate,
                'status' => TenantStatus::Trialing,
            ]);

            try {
                $scaffold->execute(function () {
                    Artisan::call('migrate', [
                        '--database' => config('multitenancy.tenant_database_connection_name', 'tenant'),
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);

                    app(AccessBaselineProvisioner::class)->ensureBaseline();

                    $settings = app(SettingManagerContract::class);
                    $settings->set('palette', 'indigo', 'theme');
                    $settings->set('mode', 'dark', 'theme');
                });
            } finally {
                $scaffold->delete();
            }

            register_shutdown_function(fn () => @unlink(self::$tenantTemplate));
        }

        return self::$tenantTemplate;
    }

    /**
     * Provision a tenant fixture: landlord row + subscription via the real
     * services, tenant DB cloned from the migrated template, owner account,
     * and the same TenantCreated/TenantProvisioned events the provisioner
     * fires. The landlord-facing steps stay real — only the per-tenant
     * migrate/baseline work is templated.
     *
     * @param  array<string, mixed>  $overrides  Passed to TenantProvisioner::provision()
     */
    protected function provisionTenant(array $overrides = []): Tenant
    {
        $data = array_merge([
            'name' => 'Test Tenant',
            'slug' => 't'.strtolower(Str::random(8)),
            'admin_name' => 'Tenant Owner',
            'admin_email' => 'owner-'.uniqid().'@test.local',
            'admin_password' => 'password123',
            'plan_id' => null,
        ], $overrides);

        $name = trim($data['name']);
        $slug = Str::slug($data['slug'] ?? $name) ?: 'tenant-'.Str::lower(Str::random(6));
        $domain = "{$slug}.localhost";
        $database = database_path('tenant_'.str_replace('-', '_', $slug).'_'.uniqid().'.sqlite');

        copy($this->tenantTemplate(), $database);

        $settings = app(SettingManagerContract::class);
        $defaultTrialDays = (int) $settings->get('default_trial_days', 14, 'system');

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'database' => $database,
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays($defaultTrialDays),
        ]);

        $plan = null;
        if (! empty($data['plan_id'])) {
            $plan = Plan::find($data['plan_id']);
        }
        if (! $plan) {
            $defaultPlanId = (int) $settings->get('default_plan_id', 0, 'billing');
            $plan = $defaultPlanId ? Plan::where('is_active', true)->find($defaultPlanId) : null;
        }
        if (! $plan) {
            $plan = Plan::where('is_active', true)->orderBy('sort_order')->first();
        }
        if ($plan) {
            app(SubscriptionService::class)->subscribeTenant($tenant, $plan, true);
        }

        $tenant->execute(function () use ($data) {
            $user = User::updateOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name' => $data['admin_name'],
                    'password' => Hash::make($data['admin_password']),
                ]
            );

            if (method_exists($user, 'assignRole')) {
                $user->assignRole(TenantPermissions::ROLE_OWNER);
            }

            if ($user->wasRecentlyCreated && method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }
        });

        event(new TenantCreated($tenant, [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
        ], $plan?->id));

        event(new TenantProvisioned($tenant));

        $this->tenantDatabaseFiles[] = $database;

        return $tenant;
    }

    /**
     * A landlord-only tenant row (no database file, no migrations) for tests
     * that never enter the tenant context.
     */
    protected function createTenantRecord(array $overrides = []): Tenant
    {
        $slug = $overrides['slug'] ?? 't'.strtolower(Str::random(8));

        $tenant = Tenant::create(array_merge([
            'name' => 'Test Tenant',
            'slug' => $slug,
            'domain' => "{$slug}.localhost",
            'database' => database_path("test_{$slug}.sqlite"),
            'status' => TenantStatus::Active,
        ], $overrides));

        $this->tenantDatabaseFiles[] = (string) $tenant->database;

        return $tenant;
    }

    /**
     * A landlord admin carrying exactly the given permissions — the scoped
     * principal for permission-gating assertions.
     *
     * @param  array<int, string>  $permissions
     */
    protected function makeScopedAdmin(string $prefix, array $permissions): LandlordUser
    {
        $role = Role::findOrCreate('T'.ucfirst($prefix).'-'.uniqid(), 'landlord');
        $role->syncPermissions($permissions);

        $admin = LandlordUser::create([
            'name' => ucfirst($prefix),
            'email' => $prefix.'-'.uniqid().'@landlord.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    protected function cleanup(LandlordUser $admin): void
    {
        $admin->roles()->detach();
        $admin->delete();
    }
}
