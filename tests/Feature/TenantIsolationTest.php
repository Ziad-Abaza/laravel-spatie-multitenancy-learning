<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantAwarePathGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_protected_routes_fail_without_tenant_context(): void
    {
        Route::get('/_test_tenant_boundary', fn () => 'ok')->middleware('tenant');

        $this->expectException(NoCurrentTenant::class);

        $this->withoutExceptionHandling()
            ->get('/_test_tenant_boundary');
    }

    public function test_login_on_landlord_host_gracefully_redirects_to_landlord_login(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect('/landlord/login');
    }

    public function test_register_on_landlord_host_gracefully_redirects_to_tenant_registration(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/register-tenant');
    }

    public function test_tenant_hint_parameters_cannot_switch_tenant_context(): void
    {
        $tenant1 = $this->provisionTenant(['slug' => 'tenant1']);
        $tenant2 = $this->provisionTenant(['slug' => 'tenant2']);

        // A client-controlled hint on a tenant domain must not switch context.
        $response = $this->get('http://tenant1.localhost/login?tenant='.$tenant2->slug);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('tenant.slug', 'tenant1'));
    }

    public function test_cross_tenant_data_is_strictly_isolated(): void
    {
        $tenant1 = $this->provisionTenant(['slug' => 'tenant1']);
        $tenant2 = $this->provisionTenant(['slug' => 'tenant2']);

        // Switch to Tenant 1
        $tenant1->makeCurrent();
        $this->assertEquals($tenant1->database, config('database.connections.tenant.database'));

        User::firstOrCreate(
            ['email' => 'unique_user_tenant_1@example.com'],
            ['name' => 'Tenant 1 Unique User', 'password' => bcrypt('password')]
        );

        // Verify Tenant 1 can see its own user
        $this->assertDatabaseHas('users', [
            'email' => 'unique_user_tenant_1@example.com',
        ], 'tenant');

        // Switch to Tenant 2
        $tenant2->makeCurrent();
        $this->assertEquals($tenant2->database, config('database.connections.tenant.database'));

        // Verify Tenant 2 DOES NOT have Tenant 1 user
        $this->assertDatabaseMissing('users', [
            'email' => 'unique_user_tenant_1@example.com',
        ], 'tenant');

        // Clean up
        Tenant::forgetCurrent();
    }

    public function test_tenant_context_scopes_cache_keyspace(): void
    {
        $original = config('cache.prefix');
        $tenant = $this->createTenantRecord();

        $tenant->makeCurrent();

        try {
            $this->assertSame('tenant_id_'.$tenant->getKey(), config('cache.prefix'));
        } finally {
            Tenant::forgetCurrent();
        }

        $this->assertSame($original, config('cache.prefix'));
    }

    public function test_tenant_schema_is_isolated_without_shared_infrastructure_tables(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'schema-check']);

        $tenant->execute(function () {
            $this->assertTrue(Schema::connection('tenant')->hasTable('users'));
            $this->assertTrue(Schema::connection('tenant')->hasTable('tenant_settings'));
            $this->assertTrue(Schema::connection('tenant')->hasTable('media'));

            // landlord-owned subsystems must not exist per-tenant; `media`
            // stays — InteractsWithMedia relations inherit the model's
            // connection so tenant uploads live per-tenant.
            foreach (['cache', 'jobs', 'sessions'] as $table) {
                $this->assertFalse(
                    Schema::connection('tenant')->hasTable($table),
                    "tenant database must not carry shared '{$table}' table"
                );
            }

            // landlord/tenant role schemas stay aligned (shared Role model)
            $this->assertTrue(Schema::connection('tenant')->hasColumn('roles', 'is_system'));
        });
    }

    public function test_media_paths_are_scoped_to_the_owning_tenant(): void
    {
        $generator = new TenantAwarePathGenerator;
        $tenant = $this->createTenantRecord();

        // Media owned by the landlord-side Tenant record (workspace logo) is
        // scoped by the owner id — resolvable identically in every context.
        $logo = new Media;
        $logo->id = 123;
        $logo->model_type = Tenant::class;
        $logo->model_id = $tenant->getKey();
        $this->assertSame("tenants/{$tenant->getKey()}/123/", $generator->getPath($logo));

        // Media owned by a tenant-side model scopes to the ambient tenant.
        $avatar = new Media;
        $avatar->id = 7;
        $avatar->model_type = User::class;
        $avatar->model_id = 1;

        $tenant->makeCurrent();
        try {
            $this->assertSame("tenants/{$tenant->getKey()}/7/", $generator->getPath($avatar));
        } finally {
            Tenant::forgetCurrent();
        }

        // Without a tenant context, landlord-owned media keeps the base path.
        $this->assertSame('7/', $generator->getPath($avatar));
    }
}
