<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;
use Modules\Landlord\Services\TenantLifecycleService;
use Tests\TestCase;

class TenantRetentionTest extends TestCase
{
    public function test_erasure_requires_archived_status(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $this->expectException(ValidationException::class);
            app(TenantLifecycleService::class)->requestErasure($tenant);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_archived_tenant_gets_grace_window_and_cancelable(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $lifecycle = app(TenantLifecycleService::class);

        try {
            $lifecycle->suspend($tenant);
            $lifecycle->archive($tenant);

            $lifecycle->requestErasure($tenant);

            $fresh = $tenant->fresh();
            $this->assertArrayHasKey('erasure_requested_at', $fresh->settings);
            $this->assertArrayHasKey('retention_until', $fresh->settings);

            $this->assertDatabaseHas('admin_audit_logs', [
                'action' => 'tenant.erasure_requested',
                'target_id' => $tenant->id,
            ]);

            $lifecycle->cancelErasure($fresh);
            $this->assertArrayNotHasKey('erasure_requested_at', $fresh->fresh()->settings);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_sweep_purges_tenant_past_retention_window(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $lifecycle = app(TenantLifecycleService::class);

        try {
            $lifecycle->suspend($tenant);
            $lifecycle->archive($tenant);
            $lifecycle->requestErasure($tenant);

            // Force the window into the past — the sweep acts on expiry.
            $settings = $tenant->fresh()->settings;
            $settings['retention_until'] = now()->subDay()->toIso8601String();
            $tenant->update(['settings' => $settings]);

            $this->artisan('tenants:enforce-lifecycle')->assertSuccessful();

            $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
            $this->assertDatabaseHas('admin_audit_logs', [
                'action' => 'tenant.deleted',
                'target_label' => $tenant->name,
            ]);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_usage_metering_records_scalars_per_tenant(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $this->artisan('tenants:record-usage')->assertSuccessful();

            $this->assertDatabaseHas('usage_records', [
                'tenant_id' => $tenant->id,
                'metric' => 'users.count',
                'value' => 1,
            ]);
            $this->assertDatabaseHas('usage_records', [
                'tenant_id' => $tenant->id,
                'metric' => 'storage.bytes',
            ]);
        } finally {
            Tenant::forgetCurrent();
        }
    }
}
