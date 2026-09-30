<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Modules\Settings\Services\SettingService;
use Tests\TestCase;

class UnifiedErrorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'tenant'])->get('/__unified_error_test_tenant_area', fn () => 'tenant area');
    }

    public function test_404_renders_unified_inertia_error_page(): void
    {
        $response = $this->get('/definitely-missing-page');

        $response->assertStatus(404);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Core/ErrorPage', false)
            ->where('status', 404)
            ->where('message', null)
            ->has('loginUrl')
            ->has('homeUrl')
        );
    }

    public function test_missing_tenant_renders_error_page_with_404_and_tenant_message(): void
    {
        $response = $this->get('/__unified_error_test_tenant_area');

        $response->assertStatus(404);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Core/ErrorPage', false)
            ->where('status', 404)
            ->where('message', __('error_tenant_not_found'))
        );
    }

    public function test_missing_tenant_json_request_returns_json_404(): void
    {
        $response = $this->getJson('/__unified_error_test_tenant_area');

        $response->assertStatus(404);
        $response->assertJson(['message' => __('error_tenant_not_found')]);
    }

    public function test_forbidden_renders_error_page_with_403(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('allow_registration', false, 'system');

        try {
            $response = $this->get('/register-tenant');

            $response->assertStatus(403);
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Core/ErrorPage', false)
                ->where('status', 403)
            );
        } finally {
            $settingService->set('allow_registration', true, 'system');
        }
    }

    public function test_unhandled_exception_renders_error_page_with_500(): void
    {
        Route::get('/__unified_error_test_boom', function () {
            throw new \RuntimeException('Boom');
        });

        $response = $this->get('/__unified_error_test_boom');

        $response->assertStatus(500);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Core/ErrorPage', false)
            ->where('status', 500)
        );
    }

    public function test_unauthenticated_requests_keep_auth_redirect(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
    }

    public function test_json_requests_keep_json_error_responses(): void
    {
        $response = $this->getJson('/definitely-missing-page');

        $response->assertStatus(404);
        $this->assertStringContainsString(
            'application/json',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_error_page_resolves_locale_without_web_middleware(): void
    {
        // Route misses never reach the web middleware stack (SetLocale /
        // HandleInertiaRequests), so the page must rebuild locale props itself.
        $this->withLocales(['en', 'ar'], function () {
            $response = $this->withHeader('Accept-Language', 'ar')->get('/definitely-missing-page');

            $response->assertStatus(404);
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Core/ErrorPage', false)
                ->where('locale.current', 'ar')
                ->where('locale.is_rtl', true)
                ->where('locale.translations.error_404_title', 'الصفحة غير موجودة')
            );
        });
    }

    public function test_error_page_uses_shared_locale_props_when_middleware_ran(): void
    {
        // Route matched: web middleware ran, shared props carry the session locale.
        $this->withLocales(['en', 'ar'], function () {
            $response = $this->withSession(['locale' => 'ar'])->get('/__unified_error_test_tenant_area');

            $response->assertStatus(404);
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Core/ErrorPage', false)
                ->where('locale.current', 'ar')
                ->where('locale.is_rtl', true)
            );
        });
    }

    /**
     * @param  array<int, string>  $locales
     */
    protected function withLocales(array $locales, callable $callback): void
    {
        $settingService = app(SettingService::class);
        $previous = array_keys($settingService->supportedLocales());
        $settingService->set('supported_locales', $locales, 'localization');

        try {
            $callback();
        } finally {
            $settingService->set('supported_locales', $previous, 'localization');
        }
    }
}
