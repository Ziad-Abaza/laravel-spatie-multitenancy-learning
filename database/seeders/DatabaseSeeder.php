<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Landlord\Database\Seeders\LandlordDatabaseSeeder;
use Modules\Settings\Models\TenantSetting;
use Spatie\Permission\Models\Role;

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

        // 1. Create standard Spatie roles on the tenant connection
        if (Schema::hasTable('roles')) {
            foreach (['Owner', 'Admin', 'Member'] as $roleName) {
                Role::findOrCreate($roleName, 'web');
            }
        }

        // 2. Create tenant owner / admin user
        if (Schema::hasTable('users')) {
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
                        $user->assignRole('Owner');
                    } catch (\Throwable) {
                    }
                }
            }
        }

        // 3. Seed default tenant settings
        if (Schema::hasTable('tenant_settings')) {
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
        }
    }
}
