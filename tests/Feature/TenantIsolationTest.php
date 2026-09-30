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
}
