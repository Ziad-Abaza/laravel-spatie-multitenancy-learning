<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLandlordContext;
use App\Models\Tenant;
use App\TenantFinder\SaaSTenantFinder;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use Modules\Core\Enums\TenantStatus;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TenancySecurityTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeTenant(array $overrides = []): Tenant
    {
        static $sequence = 0;
        $sequence++;

        return Tenant::create(array_merge([
            'name' => 'Acme '.$sequence,
            'slug' => 'acme'.$sequence,
            'domain' => 'acme'.$sequence.'.localhost',
            'database' => 'acme_db_'.$sequence,
            'status' => TenantStatus::Active,
        ], $overrides));
    }

    // ── Finder: pure resolution ──────────────────────────────────────

    public function test_finder_resolves_tenant_by_exact_domain_match(): void
    {
        $tenant = $this->makeTenant(['domain' => 'custom-acme.example.com']);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://custom-acme.example.com/'));

        $this->assertSame($tenant->getKey(), $found?->getKey());
    }

    public function test_finder_resolves_slug_under_configured_suffix(): void
    {
        $tenant = $this->makeTenant(['slug' => 'acme-suffix', 'domain' => 'acme-custom.example.com']);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://acme-suffix.localhost/'));

        $this->assertSame($tenant->getKey(), $found?->getKey());
    }

    public function test_finder_ignores_slug_on_foreign_parent_domain(): void
    {
        $this->makeTenant(['slug' => 'acme-foreign']);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://acme-foreign.evil.com/'));

        $this->assertNull($found);
    }

    public function test_finder_ignores_client_controlled_tenant_headers_and_params(): void
    {
        $tenant = $this->makeTenant();

        $finder = app(SaaSTenantFinder::class);

        $viaHeader = Request::create('http://localhost/', 'GET', [], [], [], ['HTTP_X_TENANT' => $tenant->slug]);
        $viaQuery = Request::create('http://localhost/?tenant='.$tenant->slug);

        $this->assertNull($finder->findForRequest($viaHeader));
        $this->assertNull($finder->findForRequest($viaQuery));
    }

    // ── Admission: unknown hosts fail closed ─────────────────────────

    public function test_unknown_host_gets_404_not_landlord_landing(): void
    {
        $response = $this->get('http://ghost.localhost/');

        $response->assertStatus(404);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Core/ErrorPage', false)
            ->where('status', 404)
        );
    }

    public function test_unknown_host_never_reaches_auth_redirect(): void
    {
        $response = $this->get('http://ghost.localhost/dashboard');

        $response->assertStatus(404);
    }

    public function test_unknown_host_api_request_gets_json_404(): void
    {
        $this->getJson('http://ghost.localhost/api/v1/tenants')
            ->assertStatus(404)
            ->assertJson(fn ($json) => $json->has('message'));
    }

    // ── Landlord surface ─────────────────────────────────────────────

    public function test_landlord_host_serves_landing(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_tenant_login_on_landlord_host_redirects_to_landlord_login(): void
    {
        $this->get('/login')->assertRedirect(route('landlord.login'));
    }

    public function test_landlord_area_is_rejected_in_tenant_context(): void
    {
        $middleware = new EnsureLandlordContext;

        $this->makeTenant()->makeCurrent();

        try {
            $middleware->handle(
                Request::create('http://acme1.localhost/landlord/login'),
                fn () => new Response,
            );

            $this->fail('Landlord routes must not be served in tenant context.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        } finally {
            Tenant::forgetCurrent();
        }
    }

    // ── Tenant status policy ─────────────────────────────────────────

    public function test_suspended_tenant_domain_renders_suspension_page(): void
    {
        $tenant = $this->makeTenant(['status' => TenantStatus::Suspended]);

        $response = $this->get('http://'.$tenant->domain.'/login');

        $response->assertStatus(423);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Core/ErrorPage', false)
            ->where('status', 423)
            ->where('title', __('tenant_suspended_title'))
            ->where('message', __('tenant_suspended_message'))
        );
    }

    public function test_suspended_tenant_api_request_gets_json_423(): void
    {
        $tenant = $this->makeTenant(['status' => TenantStatus::Suspended]);

        $this->getJson('http://'.$tenant->domain.'/api/v1/tenants')
            ->assertStatus(423)
            ->assertJson(fn ($json) => $json->has('message'));
    }

    public function test_archived_tenant_domain_is_blocked(): void
    {
        $tenant = $this->makeTenant(['status' => TenantStatus::Archived]);

        $this->get('http://'.$tenant->domain.'/')->assertStatus(423);
    }
}
