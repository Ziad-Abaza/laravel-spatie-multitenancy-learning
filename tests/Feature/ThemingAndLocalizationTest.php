<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Services\SettingService;
use Modules\Subscription\Models\Plan;
use Tests\TestCase;

class ThemingAndLocalizationTest extends TestCase
{
    public function test_translatable_plan_names_in_en_and_ar(): void
    {
        $plan = Plan::where('slug', 'pro')->first();
        $this->assertNotNull($plan);

        $nameEn = $plan->getTranslation('name', 'en');
        $nameAr = $plan->getTranslation('name', 'ar');

        $this->assertEquals('Professional', $nameEn);
        $this->assertEquals('المحترف', $nameAr);
    }

    public function test_bilingual_dictionary_files_exist_and_are_valid(): void
    {
        $enPath = resource_path('lang/en.json');
        $arPath = resource_path('lang/ar.json');

        $this->assertFileExists($enPath);
        $this->assertFileExists($arPath);

        $enData = json_decode(file_get_contents($enPath), true);
        $arData = json_decode(file_get_contents($arPath), true);

        $this->assertIsArray($enData);
        $this->assertIsArray($arData);
        $this->assertArrayHasKey('dashboard', $enData);
        $this->assertArrayHasKey('dashboard', $arData);
        $this->assertEquals('لوحة التحكم', $arData['dashboard']);
        $this->assertArrayHasKey('actions', $enData);
        $this->assertArrayHasKey('actions', $arData);
        $this->assertArrayHasKey('confirm_delete_title', $enData);
        $this->assertArrayHasKey('confirm_delete_title', $arData);
    }

    public function test_module_specific_translation_files_exist_in_respective_modules(): void
    {
        $settingsEn = json_decode(file_get_contents(base_path('Modules/Settings/lang/en.json')), true);
        $settingsAr = json_decode(file_get_contents(base_path('Modules/Settings/lang/ar.json')), true);
        $this->assertArrayHasKey('platform_settings', $settingsEn);
        $this->assertArrayHasKey('platform_settings', $settingsAr);
        $this->assertEquals('إعدادات المنصة المركزية', $settingsAr['platform_settings']);

        $subEn = json_decode(file_get_contents(base_path('Modules/Subscription/lang/en.json')), true);
        $subAr = json_decode(file_get_contents(base_path('Modules/Subscription/lang/ar.json')), true);
        $this->assertArrayHasKey('subscription_plans', $subEn);
        $this->assertArrayHasKey('subscription_plans', $subAr);
        $this->assertEquals('خطط الاشتراكات', $subAr['subscription_plans']);

        $landlordEn = json_decode(file_get_contents(base_path('Modules/Landlord/lang/en.json')), true);
        $landlordAr = json_decode(file_get_contents(base_path('Modules/Landlord/lang/ar.json')), true);
        $this->assertArrayHasKey('landlord_portal', $landlordEn);
        $this->assertArrayHasKey('landlord_portal', $landlordAr);

        $accessEn = json_decode(file_get_contents(base_path('Modules/Access/lang/en.json')), true);
        $accessAr = json_decode(file_get_contents(base_path('Modules/Access/lang/ar.json')), true);
        $this->assertArrayHasKey('roles', $accessEn);
        $this->assertArrayHasKey('roles', $accessAr);
    }

    public function test_translation_loader_resolves_all_module_and_global_translations(): void
    {
        $loader = app('translator')->getLoader();

        $en = $loader->load('en', '*', '*');
        $ar = $loader->load('ar', '*', '*');

        $this->assertIsArray($en);
        $this->assertIsArray($ar);

        // Global keys
        $this->assertArrayHasKey('dashboard', $en);
        $this->assertArrayHasKey('dashboard', $ar);

        // Module keys (flat access)
        $this->assertArrayHasKey('platform_settings', $en);
        $this->assertArrayHasKey('platform_settings', $ar);
        $this->assertArrayHasKey('subscription_plans', $en);
        $this->assertArrayHasKey('subscription_plans', $ar);
        $this->assertArrayHasKey('landlord_portal', $en);
        $this->assertArrayHasKey('roles', $en);
    }

    public function test_validation_translation_files_exist_and_are_valid(): void
    {
        $enValPath = resource_path('lang/en/validation.php');
        $arValPath = resource_path('lang/ar/validation.php');

        $this->assertFileExists($enValPath);
        $this->assertFileExists($arValPath);

        $enVal = require $enValPath;
        $arVal = require $arValPath;

        $this->assertIsArray($enVal);
        $this->assertIsArray($arVal);
        $this->assertArrayHasKey('required', $enVal);
        $this->assertArrayHasKey('required', $arVal);

        // Check module validation files
        $this->assertFileExists(base_path('Modules/Landlord/lang/en/validation.php'));
        $this->assertFileExists(base_path('Modules/Access/lang/en/validation.php'));
        $this->assertFileExists(base_path('Modules/Subscription/lang/en/validation.php'));
    }

    public function test_set_locale_middleware_handles_arabic_locale(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/');

        $response->assertStatus(200);
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_post_locale_switches_session_and_validates(): void
    {
        $response = $this->from('/')
            ->post('/locale', ['locale' => 'ar']);

        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'ar');

        // Test invalid locale
        $invalidResponse = $this->post('/locale', ['locale' => 'fr']);
        $invalidResponse->assertSessionHasErrors(['locale']);
    }

    public function test_post_locale_respects_supported_locales_setting(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('supported_locales', ['en'], 'localization', true);

        $response = $this->post('/locale', ['locale' => 'ar']);
        $response->assertSessionHasErrors(['locale']);

        // Restore
        $settingService->set('supported_locales', ['en', 'ar'], 'localization', true);
    }

    public function test_post_theme_updates_palette_and_mode_in_session(): void
    {
        $response = $this->from('/')
            ->post('/theme', [
                'palette' => 'emerald',
                'mode' => 'dark',
            ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('theme', 'emerald');
        $response->assertSessionHas('theme_mode', 'dark');

        // Test invalid palette
        $invalid = $this->post('/theme', [
            'palette' => 'nonexistent_color',
            'mode' => 'dark',
        ]);
        $invalid->assertSessionHasErrors(['palette']);
    }

    public function test_settings_service_persists_theme_and_palette(): void
    {
        $settingManager = app(SettingManagerContract::class);

        $settingManager->set('palette', 'violet', 'theme', true);

        $this->assertEquals('violet', $settingManager->get('palette', 'indigo', 'theme'));

        // Reset
        $settingManager->set('palette', 'indigo', 'theme', true);
    }

    public function test_settings_service_persists_canonical_keys(): void
    {
        $settingService = app(SettingService::class);

        $settingService->set('palette', 'rose', 'theme', true);
        $this->assertEquals('rose', $settingService->get('palette', null, 'theme'));
        $this->assertEquals('rose', $settingService->getTheme()['palette']);

        $settingService->set('allow_registration', false, 'system', true);
        $this->assertFalse($settingService->get('allow_registration', null, 'system'));

        // Reset
        $settingService->set('palette', 'indigo', 'theme', true);
        $settingService->set('allow_registration', true, 'system', true);
    }

    public function test_registration_blocked_when_allow_registration_is_false(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('allow_registration', false, 'system', true);

        $responseGet = $this->get('/register-tenant');
        $responseGet->assertStatus(403);

        $responsePost = $this->post('/register-tenant', [
            'organization_name' => 'Blocked Org',
            'subdomain' => 'blocked',
            'admin_name' => 'Blocked Admin',
            'admin_email' => 'admin@blocked.test',
            'admin_password' => 'secret123',
            'admin_password_confirmation' => 'secret123',
        ]);
        $responsePost->assertStatus(403);

        // Reset
        $settingService->set('allow_registration', true, 'system', true);
    }

    public function test_inertia_shares_theme_locale_and_system_props(): void
    {
        $response = $this->withSession([
            'locale' => 'en',
            'theme' => 'cyan',
            'theme_mode' => 'light',
        ])->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('theme')
            ->where('theme.palette', 'cyan')
            ->where('theme.mode', 'light')
            ->has('locale')
            ->where('locale.current', 'en')
            ->has('locale.translations')
            ->has('system.allow_registration')
            ->has('branding')
        );
    }

    public function test_root_view_renders_the_same_resolved_theme_as_shared_props(): void
    {
        $settingManager = app(SettingManagerContract::class);
        $settingManager->set('palette', 'violet', 'theme', true);

        // Persisted configuration is rendered into the HTML when no session override exists
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-theme="violet"', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('theme.theme', 'violet')
            ->where('theme.palette', 'violet')
        );

        // Session override beats persisted configuration, identically in HTML and props
        $response = $this->withSession(['theme' => 'emerald', 'theme_mode' => 'light'])->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-theme="emerald"', false);
        $response->assertSee('data-mode="light"', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('theme.theme', 'emerald')
            ->where('theme.mode', 'light')
        );

        // Invalid session values fall back to the resolved theme instead of leaking through
        $response = $this->withSession(['theme' => 'not-a-real-theme', 'theme_mode' => 'bogus'])->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-theme="violet"', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('theme.theme', 'violet'));

        $settingManager->set('palette', 'indigo', 'theme', true);
    }
}
