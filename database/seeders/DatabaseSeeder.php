<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Access\Services\AccessBaselineProvisioner;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Landlord\Database\Seeders\LandlordDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Tenant::checkCurrent()) {
            $this->runTenantSpecificSeeders();
        } else {
            $this->runLandlordSpecificSeeders();
        }
    }

    /**
     * Seeders for the Landlord database.
     */
    public function runLandlordSpecificSeeders(): void
    {
        $this->call([
            TenantSeeder::class,
            LandlordDatabaseSeeder::class,
        ]);
    }

    /**
     * Seeders for Tenant database (runs when executing tenants:artisan).
     */
    public function runTenantSpecificSeeders(): void
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return;
        }

        // 1. Permission catalog + baseline roles on the tenant connection —
        //    single source of truth is AccessBaselineProvisioner. Inside a
        //    tenant context the DEFAULT connection stays on the landlord, so
        //    every check must target the tenant connection explicitly.
        $tenantConnection = config('multitenancy.tenant_database_connection_name', 'tenant');

        if (Schema::connection($tenantConnection)->hasTable('roles')) {
            app(AccessBaselineProvisioner::class)->ensureBaseline();
        }

        // 2. Create tenant owner / admin user
        if (Schema::connection($tenantConnection)->hasTable('users')) {
            $emails = [
                'admin@'.$tenant->domain,
                'admin@'.$tenant->domain.'.com',
            ];

            foreach ($emails as $email) {
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $tenant->name.' Admin',
                        'password' => Hash::make('password'),
                        'status' => 'active',
                    ]
                );

                if (method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole(TenantPermissions::ROLE_OWNER);
                    } catch (\Throwable) {
                    }
                }
            }
        }

        // 3. Seed default tenant settings through the governed service.
        // Workspace name is tenants.name — no settings row.
        if (Schema::connection($tenantConnection)->hasTable('tenant_settings')) {
            $settings = app(SettingManagerContract::class);
            $settings->set('palette', 'indigo', 'theme', true);
            $settings->set('mode', 'dark', 'theme', true);
        }
    }
}
