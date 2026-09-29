<?php

namespace Tests\Feature;

use Modules\Core\Contracts\SettingManagerContract;
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
    }

    public function test_set_locale_middleware_handles_arabic_locale(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/');

        $response->assertStatus(200);
        $this->assertEquals('ar', app()->getLocale());
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
}
