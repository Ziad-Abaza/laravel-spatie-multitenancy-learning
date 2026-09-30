<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suffix = config('multitenancy.tenant_domain_suffix') ?: 'localhost';

        // Demo tenants on subdomains under the configured tenant domain suffix
        $tenants = [
            [
                'name' => 'Tenant 1',
                'slug' => 'tenant1',
                'domain' => "tenant1.{$suffix}",
                'database' => 'vendor_1',
            ],
            [
                'name' => 'Tenant 2',
                'slug' => 'tenant2',
                'domain' => "tenant2.{$suffix}",
                'database' => 'vendor_2',
            ],
        ];

        $hasSlug = Schema::connection('landlord')->hasColumn('tenants', 'slug');
        $hasStatus = Schema::connection('landlord')->hasColumn('tenants', 'status');

        foreach ($tenants as $data) {
            // Ensure the tenant database exists before creating or updating the record
            DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `{$data['database']}`");

            $attributes = [
                'name' => $data['name'],
                'database' => $data['database'],
            ];

            if ($hasSlug) {
                $attributes['slug'] = $data['slug'];
            }

            if ($hasStatus) {
                $attributes['status'] = 'active';
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
