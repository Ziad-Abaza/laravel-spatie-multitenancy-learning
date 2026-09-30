<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Landlord\Models\LandlordUser;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\TenantSetting;
use Modules\Settings\Services\SettingService;
use Modules\Access\Models\Role;
use Tests\TestCase;

class SettingsGovernanceTest extends TestCase
{
    protected LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = LandlordUser::firstOrCreate(
            ['email' => 'admin@landlord.test'],
            ['name' => 'Platform Admin', 'password' => bcrypt('password')]
        );
    }

    public function test_landlord_admin_can_write_a_registered_setting(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->post('/landlord/settings', [
                'domain' => 'branding',
                'settings' => ['app_name' => 'Governed Platform'],
            ]);

        $response->assertRedirect();
        $this->assertSame(
            'Governed Platform',
            Setting::where('domain', 'branding')->where('key', 'app_name')->value('value')
        );
    }

    public function test_unregistered_setting_keys_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->post('/landlord/settings', [
                'domain' => 'system',
                'settings' => ['totally_made_up_key' => 'x'],
            ]);

        $response->assertSessionHasErrors('settings.totally_made_up_key');
        $this->assertNull(Setting::where('key', 'totally_made_up_key')->first());
    }

    public function test_invalid_setting_values_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->post('/landlord/settings', [
                'domain' => 'localization',
                'settings' => ['default_locale' => 'klingon'],
            ]);

        $response->assertSessionHasErrors('settings.default_locale');
    }

    public function test_landlord_owned_keys_cannot_be_written_in_tenant_context(): void
    {
        $tenant = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant);
        $tenant->makeCurrent();

        try {
            $this->expectException(InvalidArgumentException::class);
            app(SettingService::class)->set('allow_registration', false, 'system');
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_unknown_keys_cannot_be_written_programmatically(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SettingService::class)->set('backdoor_flag', true, 'system');
    }

    public function test_member_role_cannot_update_workspace_settings(): void
    {
        $tenant = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant);
        $tenant->makeCurrent();

        $member = User::create([
            'name' => 'Member',
            'email' => 'member-'.uniqid().'@governance.test',
            'password' => bcrypt('password'),
        ]);
        $member->assignRole(Role::findOrCreate('Member', 'web'));
        $this->assertSame(['Member'], $member->fresh()->getRoleNames()->all());

        $response = $this->actingAs($member, 'web')
            ->post('http://tenant1.localhost/settings', [
                'domain' => 'branding',
                'settings' => ['tagline' => 'Hijacked'],
            ]);

        $response->assertForbidden();

        Tenant::forgetCurrent();
    }

    public function test_owner_can_update_workspace_settings_and_tenant_name_syncs(): void
    {
        $tenant = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant);
        $tenant->makeCurrent();

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner-'.uniqid().'@governance.test',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole(Role::findOrCreate('Owner', 'web'));
        $this->assertContains('Owner', $owner->fresh()->getRoleNames()->all());

        $response = $this->actingAs($owner, 'web')
            ->post('http://tenant1.localhost/settings', [
                'domain' => 'branding',
                'workspace_name' => 'Governed Workspace',
                'settings' => ['tagline' => 'Tenant tagline'],
            ]);

        $response->assertRedirect();

        // Workspace identity lives on the tenant record, not tenant_settings.
        $this->assertSame('Governed Workspace', $tenant->fresh()->name);
        $this->assertNull(
            TenantSetting::where('domain', 'branding')->where('key', 'workspace_name')->first()
        );

        Tenant::forgetCurrent();
    }

    public function test_tenant_unset_restores_landlord_inheritance(): void
    {
        $service = app(SettingService::class);

        // Landlord scope: set the platform default.
        $service->set('tagline', 'Landlord tagline', 'branding');

        $tenant = Tenant::where('domain', 'tenant1.localhost')->first();
        $this->assertNotNull($tenant);
        $tenant->makeCurrent();

        try {
            // Inherits landlord value when no tenant row exists.
            TenantSetting::where('domain', 'branding')->where('key', 'tagline')->delete();
            $this->assertSame('Landlord tagline', $service->get('tagline', null, 'branding'));

            // Tenant override wins.
            $service->set('tagline', 'Tenant override', 'branding');
            $this->assertSame('Tenant override', $service->get('tagline', null, 'branding'));

            // Unset restores inheritance.
            $service->unset('tagline', 'branding');
            $this->assertSame('Landlord tagline', $service->get('tagline', null, 'branding'));
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_direct_model_writes_invalidate_the_cache(): void
    {
        $service = app(SettingService::class);

        // Prime the cache, then write directly through the model — the
        // saved event must invalidate the domain map without set().
        $service->get('app_name', null, 'branding');
        Setting::updateOrCreate(
            ['domain' => 'branding', 'key' => 'app_name'],
            ['value' => 'Event Invalidated', 'type' => 'string']
        );

        $this->assertSame('Event Invalidated', $service->get('app_name', null, 'branding'));
    }
}
