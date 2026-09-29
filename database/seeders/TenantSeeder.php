<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hasCustomCredentials = Schema::connection('landlord')->hasColumn('tenants', 'db_username');

        $defaultUsername = env('DB_USERNAME', 'root');
        $defaultPassword = env('DB_PASSWORD', '');
        $landlordDatabase = env('DB_DATABASE', 'multivendor');

        // Landlord on localhost and tenants with subdomains on localhost
        $tenants = [
            [
                'name' => 'Landlord',
                'domain' => 'localhost',
                'database' => $landlordDatabase,
            ],
            [
                'name' => 'Tenant 1',
                'domain' => 'tenant1.localhost',
                'database' => 'vendor_1',
            ],
            [
                'name' => 'Tenant 2',
                'domain' => 'tenant2.localhost',
                'database' => 'vendor_2',
            ],
        ];

        foreach ($tenants as $data) {
            // Ensure the tenant database exists before creating or updating the record
            DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `{$data['database']}`");

            $attributes = [
                'name' => $data['name'],
                'database' => $data['database'],
            ];

            // If the tenants table has extra credentials columns (db_username, db_password)
            if ($hasCustomCredentials) {
                $attributes['db_username'] = $defaultUsername;
                $attributes['db_password'] = $defaultPassword;
            }

            Tenant::unguarded(function () use ($data, $attributes) {
                Tenant::updateOrCreate(
                    ['domain' => $data['domain']],
                    $attributes
                );
            });
        }
    }
}
