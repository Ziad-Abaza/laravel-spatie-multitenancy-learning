<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Middleware;

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

        $locale = session('locale', config('app.locale', 'en'));
        $isRtl = in_array($locale, ['ar', 'fa', 'ur', 'he'], true);

        // Load translations for locale
        $translationPath = resource_path("lang/{$locale}.json");
        $translations = [];
        if (file_exists($translationPath)) {
            $translations = json_decode(file_get_contents($translationPath), true) ?? [];
        }

        // Active Theme Settings (from tenant settings or landlord settings)
        $themeSettings = [
            'theme' => session('theme', 'indigo'),
            'mode' => session('theme_mode', 'dark'),
            'font' => $isRtl ? 'cairo' : 'inter',
        ];

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'isLandlord' => $isLandlordContext,
            ],
            'tenant' => $tenantData,
            'locale' => [
                'current' => $locale,
                'is_rtl' => $isRtl,
                'supported' => [
                    'en' => 'English',
                    'ar' => 'العربية',
                ],
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
