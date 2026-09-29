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
        $this->assertArrayHasKey('platform_settings', $enData);
        $this->assertArrayHasKey('platform_settings', $arData);
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

    public function test_post_locale_respects_enable_arabic_setting(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('enable_arabic', false, 'localization', true);

        $response = $this->post('/locale', ['locale' => 'ar']);
        $response->assertSessionHasErrors(['locale']);

        // Restore
        $settingService->set('enable_arabic', true, 'localization', true);
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

        $settingManager->set('default_palette', 'violet', 'theme', true);

        $palette = $settingManager->get('default_palette', 'indigo', 'theme');
        $this->assertEquals('violet', $palette);

        // Reset
        $settingManager->set('default_palette', 'indigo', 'theme', true);
    }

    public function test_settings_service_handles_key_aliasing(): void
    {
        $settingService = app(SettingService::class);

        // Alias: theme <=> palette <=> default_palette
        $settingService->set('theme', 'rose', 'theme', true);
        $this->assertEquals('rose', $settingService->get('default_palette', null, 'theme'));
        $this->assertEquals('rose', $settingService->get('palette', null, 'theme'));
        $this->assertEquals('rose', $settingService->get('theme', null, 'theme'));

        // Alias: allow_registration <=> registration_enabled
        $settingService->set('allow_registration', false, 'system', true);
        $this->assertFalse($settingService->get('registration_enabled', null, 'system'));
        $this->assertFalse($settingService->get('allow_registration', null, 'system'));

        // Reset
        $settingService->set('theme', 'indigo', 'theme', true);
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
}
