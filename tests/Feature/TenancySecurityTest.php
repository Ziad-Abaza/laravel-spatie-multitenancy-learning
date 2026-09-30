<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLandlordContext;
use App\Models\Tenant;
use App\TenantFinder\SaaSTenantFinder;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use Modules\Core\Enums\TenantStatus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenancySecurityTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeTenant(array $overrides = []): Tenant
    {
        $unique = uniqid('acme');

        return $this->createTenantRecord(array_merge([
            'name' => $unique,
            'slug' => $unique,
            'domain' => $unique.'.localhost',
            'status' => TenantStatus::Active,
        ], $overrides));
    }

    // ── Finder: pure resolution ──────────────────────────────────────

    public function test_finder_resolves_tenant_by_exact_domain_match(): void
    {
        $domain = uniqid('acme').'.example.com';
        $tenant = $this->makeTenant(['domain' => $domain]);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://'.$domain.'/'));

        $this->assertSame($tenant->getKey(), $found?->getKey());
    }

    public function test_finder_resolves_slug_under_configured_suffix(): void
    {
        $slug = uniqid('acme-suffix');
        $tenant = $this->makeTenant(['slug' => $slug, 'domain' => $slug.'.example.com']);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://'.$slug.'.localhost/'));

        $this->assertSame($tenant->getKey(), $found?->getKey());
    }

    public function test_finder_ignores_slug_on_foreign_parent_domain(): void
    {
        $slug = uniqid('acme-foreign');
        $this->makeTenant(['slug' => $slug]);

        $found = app(SaaSTenantFinder::class)->findForRequest(Request::create('http://'.$slug.'.evil.com/'));

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
        $this->getJson('http://ghost.localhost/dashboard')
            ->assertStatus(404)
            ->assertJson(fn ($json) => $json->has('message'));
    }

    public function test_sanctum_scaffold_api_routes_are_not_registered(): void
    {
        // No API surface exists: every /api/v1/* scaffold resource must 404
        // rather than 500 on the missing sanctum middleware.
        foreach (['accesses', 'landlords', 'subscriptions', 'tenants'] as $resource) {
            $this->getJson("/api/v1/{$resource}")->assertNotFound();
        }
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
        } catch (HttpException $e) {
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

        $this->getJson('http://'.$tenant->domain.'/login')
            ->assertStatus(423)
            ->assertJson(fn ($json) => $json->has('message'));
    }

    public function test_archived_tenant_domain_is_blocked(): void
    {
        $tenant = $this->makeTenant(['status' => TenantStatus::Archived]);

        $this->get('http://'.$tenant->domain.'/')->assertStatus(423);
    }
}
