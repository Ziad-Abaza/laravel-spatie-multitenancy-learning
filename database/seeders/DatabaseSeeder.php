<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

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
        ]);

        // Only create admin user if the users table exists in the landlord database
        if (Schema::hasTable('users')) {
            User::updateOrCreate(
                ['email' => 'admin@localhost'],
                [
                    'name' => 'Landlord Admin',
                    'password' => bcrypt('password'),
                ]
            );
        }
    }

    /**
     * Seeders for Tenant database (runs when executing tenants:artisan).
     */
    public function runTenantSpecificSeeders(): void
    {
        $tenant = Tenant::current();

        // Only create admin user if the users table exists in the tenant database
        if (Schema::hasTable('users')) {
            User::updateOrCreate(
                ['email' => 'admin@' . $tenant->domain],
                [
                    'name' => $tenant->name . ' Admin',
                    'password' => bcrypt('password'),
                ]
            );
        }
    }
}
