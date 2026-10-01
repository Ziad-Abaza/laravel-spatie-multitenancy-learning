<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\URL;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Landlord\Models\TenantBackup;
use Modules\Landlord\Services\LandlordMetricsService;
use Modules\Landlord\Services\TenantBackupService;
use Modules\Landlord\Services\TenantLifecycleService;
use Tests\TestCase;

class TenantBackupTest extends TestCase
{
    public function test_backup_command_creates_registry_row_and_file(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $this->artisan('tenants:backup', ['tenant' => 'tenant1'])->assertSuccessful();

            $backup = TenantBackup::where('tenant_id', $tenant->id)->firstOrFail();
            $this->assertSame('completed', $backup->status);
            $this->assertFileExists($backup->absolutePath());
            $this->assertGreaterThan(0, $backup->size);

            // Cleanup artifact.
            app(TenantBackupService::class)->delete($backup);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_backup_service_deletes_file_with_row(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $service = app(TenantBackupService::class);
            $backup = $service->create($tenant);
            $path = $backup->absolutePath();

            $service->delete($backup);

            $this->assertFileDoesNotExist($path);
            $this->assertDatabaseMissing('tenant_backups', ['id' => $backup->id]);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_backup_store_is_permission_gated(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $viewer = $this->makeScopedAdmin('viewer', [LP::TENANTS_VIEW]);
        $exporter = $this->makeScopedAdmin('exporter', [LP::TENANTS_VIEW, LP::TENANTS_EXPORT]);

        try {
            $this->actingAs($viewer, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/backups")
                ->assertForbidden();

            $this->flushSession();

            $this->actingAs($exporter, 'landlord')
                ->post("/landlord/tenants/{$tenant->id}/backups")
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('tenant_backups', ['tenant_id' => $tenant->id]);

            TenantBackup::where('tenant_id', $tenant->id)
                ->each(fn ($b) => app(TenantBackupService::class)->delete($b));
        } finally {
            $this->cleanup($viewer);
            $this->cleanup($exporter);
            Tenant::forgetCurrent();
        }
    }

    public function test_backup_download_requires_valid_signature(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $admin = $this->makeScopedAdmin('dl', [LP::TENANTS_VIEW]);

        try {
            $backup = app(TenantBackupService::class)->create($tenant);

            $this->actingAs($admin, 'landlord')
                ->get("/landlord/tenants/{$tenant->id}/backups/{$backup->id}/download")
                ->assertForbidden();

            $signed = URL::temporarySignedRoute(
                'landlord.tenants.backups.download',
                now()->addHour(),
                ['tenant' => $tenant->id, 'backup' => $backup->id]
            );

            $this->flushSession();
            $this->actingAs($admin, 'landlord')
                ->get($signed)
                ->assertOk();

            $this->assertDatabaseHas('admin_audit_logs', [
                'action' => 'tenant.backup_downloaded',
                'target_id' => $tenant->id,
            ]);

            app(TenantBackupService::class)->delete($backup);
        } finally {
            $this->cleanup($admin);
            Tenant::forgetCurrent();
        }
    }

    public function test_tenant_delete_purges_backup_directory(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            app(TenantBackupService::class)->create($tenant);
            $dir = storage_path("app/backups/{$tenant->slug}");
            $this->assertDirectoryExists($dir);

            app(TenantLifecycleService::class)->delete($tenant, dropDatabase: true);

            $this->assertDirectoryDoesNotExist($dir);
            $this->assertDatabaseMissing('tenant_backups', ['tenant_id' => $tenant->id]);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_diagnostics_return_reachable_aggregates_and_degrade(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $metrics = app(LandlordMetricsService::class);

        try {
            $ok = $metrics->tenantDiagnostics($tenant);
            $this->assertTrue($ok['reachable']);
            $this->assertSame(1, $ok['user_count']);

            // A tenant whose database file vanished degrades, not throws.
            $tenant->database = '/nonexistent/path/dead.sqlite';
            $dead = $metrics->tenantDiagnostics($tenant);
            $this->assertFalse($dead['reachable']);
            $this->assertNull($dead['user_count']);
        } finally {
            Tenant::forgetCurrent();
        }
    }
}
