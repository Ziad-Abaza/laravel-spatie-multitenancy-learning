<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

    public function test_tenant_request_succeeds_with_tenant_query_or_domain(): void
    {
        $tenant = Tenant::first();
        $this->assertNotNull($tenant);

        $response = $this->get('/login?tenant='.$tenant->slug);

        $response->assertStatus(200);
    }

    public function test_cross_tenant_data_is_strictly_isolated(): void
    {
        $tenant1 = Tenant::where('domain', 'tenant1.localhost')->first();
        $tenant2 = Tenant::where('domain', 'tenant2.localhost')->first();

        $this->assertNotNull($tenant1);
        $this->assertNotNull($tenant2);

        // Switch to Tenant 1
        $tenant1->makeCurrent();
        $this->assertEquals('vendor_1', config('database.connections.tenant.database'));

        $tenant1User = User::firstOrCreate(
            ['email' => 'unique_user_tenant_1@example.com'],
            ['name' => 'Tenant 1 Unique User', 'password' => bcrypt('password')]
        );

        // Verify Tenant 1 can see its own user
        $this->assertDatabaseHas('users', [
            'email' => 'unique_user_tenant_1@example.com',
        ], 'tenant');

        // Switch to Tenant 2
        $tenant2->makeCurrent();
        $this->assertEquals('vendor_2', config('database.connections.tenant.database'));

        // Verify Tenant 2 DOES NOT have Tenant 1 user
        $this->assertDatabaseMissing('users', [
            'email' => 'unique_user_tenant_1@example.com',
        ], 'tenant');

        // Clean up
        Tenant::forgetCurrent();
    }
}
