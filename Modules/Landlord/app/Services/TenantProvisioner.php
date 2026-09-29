<?php

namespace Modules\Landlord\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Landlord\Models\Tenant;
use Modules\Settings\Models\TenantSetting;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Permission\Models\Role;

class TenantProvisioner
{
    public function __construct(
        protected SubscriptionService $subscriptionService
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

        // Ensure database name is safe
        $databaseName = 'vendor_'.str_replace('-', '_', $slug);

        // 1. Create tenant database if MySQL (or prepare sqlite in test)
        $this->createDatabaseIfNotExists($databaseName);

        // 2. Create Tenant record on landlord connection
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'database' => $databaseName,
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
            'settings' => [
                'theme' => 'indigo',
                'mode' => 'dark',
                'language' => 'en',
            ],
        ]);

        // 3. Assign Plan and Subscription
        $plan = null;
        if (! empty($data['plan_id'])) {
            $plan = Plan::find($data['plan_id']);
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
        ]);

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
    protected function seedTenantInitialData(Tenant $tenant, array $adminData): void
    {
        $tenant->execute(function () use ($adminData, $tenant) {
            // Setup default roles on tenant connection
            $roles = ['Owner', 'Admin', 'Member'];
            foreach ($roles as $roleName) {
                Role::findOrCreate($roleName, 'web');
            }

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

            // Default tenant settings
            TenantSetting::updateOrCreate(
                ['domain' => 'branding', 'key' => 'company_name'],
                ['value' => $tenant->name, 'type' => 'string', 'is_public' => true]
            );
            TenantSetting::updateOrCreate(
                ['domain' => 'theme', 'key' => 'theme'],
                ['value' => 'indigo', 'type' => 'string', 'is_public' => true]
            );
            TenantSetting::updateOrCreate(
                ['domain' => 'theme', 'key' => 'mode'],
                ['value' => 'dark', 'type' => 'string', 'is_public' => true]
            );
        });
    }
}
