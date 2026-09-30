<?php

namespace Modules\Landlord\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Landlord\Models\Tenant;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;
use Spatie\Multitenancy\Actions\MigrateTenantAction;

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

        // Domain construction
        $baseHost = request()->getHost() ?: 'localhost';
        if (str_contains($baseHost, ':')) {
            $baseHost = explode(':', $baseHost)[0];
        }
        $domain = $data['domain'] ?? "{$slug}.{$baseHost}";

        // Ensure database name is safe with configurable prefix
        $prefix = (string) $this->settingService->get('tenant_db_prefix', 'tenant_', 'system');
        $databaseName = $prefix.str_replace('-', '_', $slug);

        // 1. Create tenant database if MySQL (or prepare sqlite in test)
        $this->createDatabaseIfNotExists($databaseName);

        // Retrieve system defaults for trial duration and theme
        $defaultTrialDays = (int) $this->settingService->get('default_trial_days', 14, 'system');
        $defaultPalette = (string) $this->settingService->get('palette', 'indigo', 'theme');
        $defaultMode = (string) $this->settingService->get('mode', 'dark', 'theme');

        // 2. Create Tenant record on landlord connection
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'database' => $databaseName,
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
     * Create the database if it doesn't already exist.
     */
    protected function createDatabaseIfNotExists(string $databaseName): void
    {
        $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');
        $driver = config("database.connections.{$landlordConnection}.driver", 'mysql');

        if ($driver === 'sqlite') {
            $path = database_path("{$databaseName}.sqlite");
            if (! file_exists($path)) {
                touch($path);
            }
        } else {
            DB::connection($landlordConnection)->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    /**
     * Run tenant migrations inside tenant database.
     */
    protected function runTenantMigrations(Tenant $tenant): void
    {
        app(MigrateTenantAction::class)->execute($tenant);
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
                $user->assignRole('Owner');
            }

            // Default tenant settings — always through the governed service
            // (registry validation + cache invalidation).
            $settings = app(SettingManagerContract::class);
            $settings->set('palette', $defaultPalette, 'theme', true);
            $settings->set('mode', $defaultMode, 'theme', true);
        });
    }
}
