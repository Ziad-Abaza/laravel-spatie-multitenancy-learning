<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Core\Contracts\TranslationResolverContract;
use Modules\Core\Services\ThemeResolver;
use Modules\Settings\Services\SettingService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentTenant = Tenant::current();
        $isLandlordContext = $currentTenant === null;

        $user = null;
        if ($isLandlordContext && auth('landlord')->check()) {
            $landlordUser = auth('landlord')->user();
            $user = [
                'id' => $landlordUser->id,
                'name' => $landlordUser->name,
                'email' => $landlordUser->email,
                'is_landlord' => true,
                'roles' => method_exists($landlordUser, 'getRoleNames') ? $landlordUser->getRoleNames() : ['Super Admin'],
                'permissions' => method_exists($landlordUser, 'getAllPermissions') ? $landlordUser->getAllPermissions()->pluck('name') : [],
            ];
        } elseif (auth('web')->check()) {
            $tenantUser = auth('web')->user();
            $user = [
                'id' => $tenantUser->id,
                'name' => $tenantUser->name,
                'email' => $tenantUser->email,
                'is_landlord' => false,
                'status' => $tenantUser->status ?? 'active',
                'roles' => method_exists($tenantUser, 'getRoleNames') ? $tenantUser->getRoleNames() : ['Member'],
                'permissions' => method_exists($tenantUser, 'getAllPermissions') ? $tenantUser->getAllPermissions()->pluck('name') : [],
            ];
        }

        $tenantData = null;
        if ($currentTenant) {
            $tenantData = [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name,
                'slug' => $currentTenant->slug ?? $currentTenant->domain,
                'domain' => $currentTenant->domain,
                'status' => $currentTenant->status ?? 'active',
                'plan' => $currentTenant->plan ? [
                    'id' => $currentTenant->plan->id,
                    'name' => $currentTenant->plan->name,
                    'slug' => $currentTenant->plan->slug,
                    'limits' => $currentTenant->plan->limits ?? [],
                ] : null,
                'settings' => $currentTenant->settings ?? [],
            ];
        }

        $settingService = app(SettingService::class);
        $branding = $settingService->getBranding();

        if ($currentTenant) {
            $tenantData = [
                'id' => $currentTenant->id,
                'name' => $branding['workspace_name'] ?? $currentTenant->name,
                'slug' => $currentTenant->slug ?? $currentTenant->domain,
                'domain' => $currentTenant->domain,
                'status' => $currentTenant->status ?? 'active',
                'plan' => $currentTenant->plan ? [
                    'id' => $currentTenant->plan->id,
                    'name' => $currentTenant->plan->getName(),
                    'slug' => $currentTenant->plan->slug,
                    'limits' => $currentTenant->plan->limits ?? [],
                ] : null,
                'settings' => $currentTenant->settings ?? [],
                'branding' => $branding,
            ];
        }

        $appName = $branding['app_name'] ?? $branding['company_name'] ?? config('app.name', 'SaaS Platform');
        config(['app.name' => $appName]);

        $enableArabic = (bool) $settingService->get('enable_arabic', true, 'localization');
        $supportedLocales = ['en' => 'English'];
        if ($enableArabic) {
            $supportedLocales['ar'] = 'العربية';
        }

        $defaultLocale = $settingService->get('default_locale', config('app.locale', 'en'), 'localization');
        if (! array_key_exists($defaultLocale, $supportedLocales)) {
            $defaultLocale = 'en';
        }

        $locale = session('locale', $defaultLocale);
        if (! array_key_exists($locale, $supportedLocales)) {
            $locale = $defaultLocale;
            session(['locale' => $locale]);
        }
        app()->setLocale($locale);

        $isRtl = in_array($locale, ['ar', 'fa', 'ur', 'he'], true);

        // Resolve translations from global layer and active modules via TranslationResolver
        $translationResolver = app(TranslationResolverContract::class);
        $translations = $translationResolver->resolve($locale);

        // Active Theme Settings — resolved identically for props and Blade root view
        $activeTheme = app(ThemeResolver::class)->resolve();

        $themeSettings = [
            'theme' => $activeTheme['theme'],
            'palette' => $activeTheme['palette'],
            'mode' => $activeTheme['mode'],
            'font' => $isRtl ? 'cairo' : 'inter',
        ];

        $allowRegistration = (bool) $settingService->get('allow_registration', $settingService->get('registration_enabled', true, 'system'), 'system');

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'isLandlord' => $isLandlordContext,
            ],
            'tenant' => $tenantData,
            'branding' => $branding,
            'system' => [
                'allow_registration' => $allowRegistration,
            ],
            'locale' => [
                'current' => $locale,
                'is_rtl' => $isRtl,
                'supported' => $supportedLocales,
                'translations' => $translations,
            ],
            'theme' => $themeSettings,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ]);
    }
}
