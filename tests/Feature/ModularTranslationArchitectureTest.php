<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ModularTranslationArchitectureTest extends TestCase
{
    protected array $modules = ['Core', 'Access', 'Landlord', 'Subscription', 'Settings', 'Tenant'];

    public function test_each_module_owns_its_own_translation_files(): void
    {
        foreach ($this->modules as $module) {
            $enFile = base_path("Modules/{$module}/lang/en.json");
            $arFile = base_path("Modules/{$module}/lang/ar.json");

            $this->assertFileExists($enFile, "Module {$module} is missing lang/en.json");
            $this->assertFileExists($arFile, "Module {$module} is missing lang/ar.json");

            $enContent = json_decode(file_get_contents($enFile), true);
            $arContent = json_decode(file_get_contents($arFile), true);

            $this->assertIsArray($enContent, "Module {$module} lang/en.json is not valid JSON");
            $this->assertIsArray($arContent, "Module {$module} lang/ar.json is not valid JSON");
            $this->assertNotEmpty($enContent, "Module {$module} lang/en.json is empty");
            $this->assertNotEmpty($arContent, "Module {$module} lang/ar.json is empty");

            // English and Arabic files in the same module should have identical key sets
            $this->assertEquals(
                array_keys($enContent),
                array_keys($arContent),
                "Module {$module} has mismatched keys between en.json and ar.json"
            );
        }
    }

    public function test_global_translation_layer_is_lean_and_shared_only(): void
    {
        $globalEn = json_decode(file_get_contents(resource_path('lang/en.json')), true);
        $globalAr = json_decode(file_get_contents(resource_path('lang/ar.json')), true);

        $this->assertIsArray($globalEn);
        $this->assertIsArray($globalAr);

        // Global layer stays limited to shared primitives — domain terms live in their module
        $this->assertLessThanOrEqual(60, count($globalEn));
        $this->assertLessThanOrEqual(60, count($globalAr));

        // Shared primitives belong here
        $this->assertArrayHasKey('actions', $globalEn);
        $this->assertArrayHasKey('save', $globalEn);
        $this->assertArrayHasKey('cancel', $globalEn);
        $this->assertArrayHasKey('status', $globalEn);
        $this->assertArrayHasKey('active', $globalEn);
        $this->assertArrayHasKey('inactive', $globalEn);

        // Module-specific domain terms must NOT be in the global layer
        $this->assertArrayNotHasKey('platform_settings', $globalEn);
        $this->assertArrayNotHasKey('subscription_plans', $globalEn);
        $this->assertArrayNotHasKey('landlord_portal', $globalEn);
        $this->assertArrayNotHasKey('register_tenant_title', $globalEn);
        $this->assertArrayNotHasKey('admin_password_label', $globalEn);
    }

    public function test_no_duplicate_keys_across_modules_and_global_layer(): void
    {
        $globalEn = json_decode(file_get_contents(resource_path('lang/en.json')), true);
        $allKeys = [];

        foreach ($globalEn as $k => $v) {
            $allKeys[$k] = 'global';
        }

        foreach ($this->modules as $module) {
            $modEn = json_decode(file_get_contents(base_path("Modules/{$module}/lang/en.json")), true);
            foreach ($modEn as $k => $v) {
                $existingLocation = $allKeys[$k] ?? 'unknown';
                $this->assertArrayNotHasKey(
                    $k,
                    $allKeys,
                    "Translation key '{$k}' in module {$module} is duplicated in {$existingLocation}"
                );
                $allKeys[$k] = $module;
            }
        }
    }

    public function test_translation_loader_discovers_all_keys_for_en_and_ar(): void
    {
        $loader = app('translator')->getLoader();

        $enResolved = $loader->load('en', '*', '*');
        $arResolved = $loader->load('ar', '*', '*');

        // Test English resolutions
        $this->assertEquals('Dashboard', $enResolved['dashboard'] ?? null);
        $this->assertEquals('Platform Settings', $enResolved['platform_settings'] ?? null);
        $this->assertEquals('Subscription Plans', $enResolved['subscription_plans'] ?? null);
        $this->assertEquals('Landlord Administration', $enResolved['landlord_portal'] ?? null);
        $this->assertEquals('Role-Based Access Control', $enResolved['rbac_security'] ?? null);
        $this->assertEquals('Deploy your isolated workspace in seconds', $enResolved['register_tenant_subtitle'] ?? null);

        // Test Arabic resolutions
        $this->assertEquals('لوحة التحكم', $arResolved['dashboard'] ?? null);
        $this->assertEquals('إعدادات المنصة المركزية', $arResolved['platform_settings'] ?? null);
        $this->assertEquals('خطط الاشتراكات', $arResolved['subscription_plans'] ?? null);
        $this->assertEquals('لوحة تحكم المنصة المركزية (Landlord)', $arResolved['landlord_portal'] ?? null);
        $this->assertEquals('التحكم بالوصول المبني على الأدوار (RBAC)', $arResolved['rbac_security'] ?? null);
        $this->assertEquals('احصل على قاعدة بيانات ومساحة عمل معزولة بالكامل خلال ثوانٍ', $arResolved['register_tenant_subtitle'] ?? null);
    }

    public function test_laravel_translator_finds_module_translations_via_trans_and_underscore(): void
    {
        app()->setLocale('en');
        $this->assertEquals('Platform Settings', __('platform_settings'));
        $this->assertEquals('Subscription Plans', __('subscription_plans'));
        $this->assertEquals('Landlord Administration', __('landlord_portal'));

        app()->setLocale('ar');
        $this->assertEquals('إعدادات المنصة المركزية', __('platform_settings'));
        $this->assertEquals('خطط الاشتراكات', __('subscription_plans'));
        $this->assertEquals('لوحة تحكم المنصة المركزية (Landlord)', __('landlord_portal'));
    }

    public function test_module_validation_attributes_are_registered_and_translated(): void
    {
        app()->setLocale('en');
        $validatorEn = Validator::make([], [
            'organization_name' => 'required',
            'plan_id' => 'required',
        ]);
        $errorsEn = $validatorEn->errors();
        $this->assertEquals('The organization name field is required.', $errorsEn->first('organization_name'));
        $this->assertEquals('The subscription plan field is required.', $errorsEn->first('plan_id'));

        app()->setLocale('ar');
        $validatorAr = Validator::make([], [
            'organization_name' => 'required',
            'plan_id' => 'required',
        ]);
        $errorsAr = $validatorAr->errors();
        $this->assertEquals('حقل اسم المنشأة مطلوب.', $errorsAr->first('organization_name'));
        $this->assertEquals('حقل خطة الاشتراك مطلوب.', $errorsAr->first('plan_id'));
    }

    public function test_inertia_receives_resolved_translations_in_both_locales(): void
    {
        $responseEn = $this->withSession(['locale' => 'en'])->get('/');
        $responseEn->assertStatus(200);
        $responseEn->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.current', 'en')
            ->where('locale.is_rtl', false)
            ->has('locale.translations')
            ->where('locale.translations.platform_settings', 'Platform Settings')
            ->where('locale.translations.subscription_plans', 'Subscription Plans')
        );

        $responseAr = $this->withSession(['locale' => 'ar'])->get('/');
        $responseAr->assertStatus(200);
        $responseAr->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.current', 'ar')
            ->where('locale.is_rtl', true)
            ->has('locale.translations')
            ->where('locale.translations.platform_settings', 'إعدادات المنصة المركزية')
            ->where('locale.translations.subscription_plans', 'خطط الاشتراكات')
        );
    }
}
