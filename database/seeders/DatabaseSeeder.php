<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Access\Models\Role;
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

        // 3. Seed default tenant settings through the governed service.
        // Workspace name is tenants.name — no settings row.
        if (Schema::hasTable('tenant_settings')) {
            $settings = app(SettingManagerContract::class);
            $settings->set('palette', 'indigo', 'theme', true);
            $settings->set('mode', 'dark', 'theme', true);
        }
    }
}
