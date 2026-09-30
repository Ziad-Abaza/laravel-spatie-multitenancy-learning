<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;

class TenantProvisioner
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected SettingManagerContract $settingService
    ) {}

    /**
     * Provision a complete new tenant: create record, create DB, run migrations, seed owner & roles, attach plan.
     *
     * @param array{
     *     name: string,
     *     slug?: string,
     *     domain?: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     plan_id?: int|string|null,
     *     billing_interval?: string
     * } $data
     */
    public function provision(array $data): Tenant
    {
        $name = trim($data['name']);
        $slug = Str::slug($data['slug'] ?? $name);

        if (empty($slug)) {
            $slug = 'tenant-'.Str::lower(Str::random(6));
        }

        // Domain construction — single source of truth: tenantDomain()
        $domain = $data['domain'] ?? $this->tenantDomain($slug);

        // Ensure database name is safe with configurable prefix. On sqlite the
        // tenant record stores the database file path (that is what the
        // tenant connection consumes); on MySQL it stores the schema name.
        $prefix = (string) $this->settingService->get('tenant_db_prefix', 'tenant_', 'system');
        $databaseName = $prefix.str_replace('-', '_', $slug);
        $database = $this->isSqliteDriver()
            ? database_path("{$databaseName}.sqlite")
            : $databaseName;

        // 1. Create tenant database if MySQL (or prepare sqlite in test)
        $this->createDatabaseIfNotExists($database);

        // Retrieve system defaults for trial duration and theme
        $defaultTrialDays = (int) $this->settingService->get('default_trial_days', 14, 'system');
        $defaultPalette = (string) $this->settingService->get('palette', 'indigo', 'theme');
        $defaultMode = (string) $this->settingService->get('mode', 'dark', 'theme');

        // 2. Create Tenant record on landlord connection
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'database' => $database,
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays($defaultTrialDays),
        ]);

        // 3. Assign Plan and Subscription — explicit choice, then the
        // configured default plan, then the lowest active plan as last resort.
        $plan = null;
        if (! empty($data['plan_id'])) {
            $plan = Plan::find($data['plan_id']);
        }
        if (! $plan) {
            $defaultPlanId = (int) $this->settingService->get('default_plan_id', 0, 'billing');
            $plan = $defaultPlanId ? Plan::where('is_active', true)->find($defaultPlanId) : null;
        }
        if (! $plan) {
            $plan = Plan::where('is_active', true)->orderBy('sort_order')->first();
        }

        if ($plan) {
            $this->subscriptionService->subscribeTenant(
                $tenant,
                $plan,
                $data['billing_interval'] ?? 'monthly',
                true
            );
        }

        // 4. Run tenant migrations
        $this->runTenantMigrations($tenant);

        // 5. Seed owner user and roles in the tenant database
        $this->seedTenantInitialData($tenant, [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
        ], $defaultPalette, $defaultMode);

        event(new TenantCreated($tenant, [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
        ], $plan?->id));

        event(new TenantProvisioned($tenant));

        return $tenant;
    }

    /**
     * Canonical tenant domain for a slug: {slug}.{TENANT_DOMAIN_SUFFIX}.
     * Falls back to the current request host when no suffix is configured.
     * This is the single construction point — callers must not hardcode
     * a suffix.
     */
    public function tenantDomain(string $slug): string
    {
        $host = config('multitenancy.tenant_domain_suffix')
            ?: (request()->getHost() ?: 'localhost');

        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        return strtolower($slug).'.'.strtolower($host);
    }

    /**
     * Create the database if it doesn't already exist. For sqlite, $database
     * is the database file path; for MySQL it is the schema name.
     */
    protected function createDatabaseIfNotExists(string $database): void
    {
        if ($this->isSqliteDriver()) {
            if (! file_exists($database)) {
                touch($database);
            }

            return;
        }

        $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');

        DB::connection($landlordConnection)->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    protected function isSqliteDriver(): bool
    {
        $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');

        return config("database.connections.{$landlordConnection}.driver", 'mysql') === 'sqlite';
    }

    /**
     * Run tenant migrations inside the tenant database. The explicit
     * --database=tenant is mandatory: SwitchTenantDatabaseTask only repoints
     * the `tenant` connection and never changes database.default, so a bare
     * `migrate` here would run against the landlord connection.
     */
    protected function runTenantMigrations(Tenant $tenant): void
    {
        $tenant->execute(function () {
            throw_unless(Tenant::checkCurrent(), \RuntimeException::class, 'Tenant context switch failed');

            Artisan::call('migrate', [
                '--database' => config('multitenancy.tenant_database_connection_name', 'tenant'),
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }

    /**
     * Seed initial tenant data: Owner user, Spatie roles (Owner, Admin, Member), default settings.
     */
    protected function seedTenantInitialData(Tenant $tenant, array $adminData, string $defaultPalette = 'indigo', string $defaultMode = 'dark'): void
    {
        $tenant->execute(function () use ($adminData, $defaultPalette, $defaultMode) {
            // Permission catalog + baseline roles (Owner/Admin/Member) on tenant connection
            app(AccessBaselineProvisioner::class)->ensureBaseline();

            // Create initial Owner user
            $user = User::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make($adminData['password']),
                ]
            );

            // Assign Owner role
            if (method_exists($user, 'assignRole')) {
                $user->assignRole(TenantPermissions::ROLE_OWNER);
            }

            // Default tenant settings — always through the governed service
            // (registry validation + cache invalidation).
            $settings = app(SettingManagerContract::class);
            $settings->set('palette', $defaultPalette, 'theme', true);
            $settings->set('mode', $defaultMode, 'theme', true);
        });
    }
}
