<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Services\TenantProvisioner;
use Modules\Subscription\Database\Seeders\PlanSeeder;

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
     * Provision a tenant through the real TenantProvisioner: landlord row,
     * tenant database file (sqlite under the test driver), migrations,
     * baseline roles, owner account, and default settings.
     *
     * @param  array<string, mixed>  $overrides  Passed to TenantProvisioner::provision()
     */
    protected function provisionTenant(array $overrides = []): Tenant
    {
        $tenant = app(TenantProvisioner::class)->provision(array_merge([
            'name' => 'Test Tenant',
            'slug' => 't'.strtolower(Str::random(8)),
            'admin_name' => 'Tenant Owner',
            'admin_email' => 'owner-'.uniqid().'@test.local',
            'admin_password' => 'password123',
            'plan_id' => null,
        ], $overrides));

        $this->tenantDatabaseFiles[] = (string) $tenant->database;

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
}
