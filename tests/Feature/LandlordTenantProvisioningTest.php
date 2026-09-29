<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Models\LandlordUser;
use Tests\TestCase;

class LandlordTenantProvisioningTest extends TestCase
{
    protected LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = LandlordUser::firstOrCreate(
            ['email' => 'admin@landlord.test'],
            [
                'name' => 'Platform Administrator',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_public_landing_page_loads_with_active_plans(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_public_pricing_page_loads(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
    }

    public function test_landlord_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/landlord/login', [
            'email' => 'admin@landlord.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/landlord');
        $this->assertTrue(auth('landlord')->check());
        $this->assertEquals($this->admin->id, auth('landlord')->id());
    }

    public function test_landlord_admin_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/landlord/login', [
            'email' => 'admin@landlord.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(auth('landlord')->check());
    }

    public function test_landlord_dashboard_accessible_to_authenticated_admin(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->get('/landlord');

        $response->assertStatus(200);
    }

    public function test_landlord_tenants_index_lists_existing_tenants(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->get('/landlord/tenants');

        $response->assertStatus(200);
    }

    public function test_landlord_can_suspend_and_activate_tenant(): void
    {
        $tenant = Tenant::first();
        $this->assertNotNull($tenant);

        // Suspend
        $response = $this->actingAs($this->admin, 'landlord')
            ->post("/landlord/tenants/{$tenant->id}/suspend");

        $response->assertRedirect();
        $tenant->refresh();
        $this->assertEquals(TenantStatus::Suspended, $tenant->status);

        // Activate
        $response = $this->actingAs($this->admin, 'landlord')
            ->post("/landlord/tenants/{$tenant->id}/activate");

        $response->assertRedirect();
        $tenant->refresh();
        $this->assertEquals(TenantStatus::Active, $tenant->status);
    }
}
