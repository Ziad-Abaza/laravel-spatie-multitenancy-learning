<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\Currency;
use Modules\Core\Enums\Locale;
use Modules\Core\Enums\ThemePalette;

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
                'roles' => $landlordUser->getRoleNames(),
                'permissions' => $landlordUser->getAllPermissions()->pluck('name'),
            ];
        } elseif (auth('web')->check()) {
            $tenantUser = auth('web')->user();
            $user = [
                'id' => $tenantUser->id,
                'name' => $tenantUser->name,
                'email' => $tenantUser->email,
                'is_landlord' => false,
                'status' => $tenantUser->status ?? 'active',
                'roles' => $tenantUser->getRoleNames(),
                'permissions' => $tenantUser->getAllPermissions()->pluck('name'),
            ];
        }

        $settings = app(SettingManagerContract::class);
        $branding = $settings->getBranding();

        $tenantData = null;
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
                'branding' => $branding,
            ];
        }

        // The SetLocale middleware has already resolved and applied the locale.
        $locale = app()->getLocale();
        $supportedLocales = $settings->supportedLocales();
        if (! array_key_exists($locale, $supportedLocales)) {
            $locale = $settings->defaultLocale();
            app()->setLocale($locale);
        }

        $isRtl = Locale::tryFrom($locale)?->isRtl() ?? false;

        $theme = $settings->getTheme();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'isLandlord' => $isLandlordContext,
            ],
            'tenant' => $tenantData,
            'branding' => $branding,
            'system' => [
                'allow_registration' => (bool) $settings->get('allow_registration', true, 'system'),
            ],
            'tenancy' => [
                'domain_suffix' => config('multitenancy.tenant_domain_suffix'),
            ],
            'billing' => [
                'currency' => $settings->get('default_currency', Currency::Usd->value, 'billing'),
            ],
            'locale' => [
                'current' => $locale,
                'is_rtl' => $isRtl,
                'supported' => $supportedLocales,
                'translations' => fn () => $this->translationsFor($request, $locale),
            ],
            'theme' => [
                'theme' => $theme['theme'],
                'palette' => $theme['palette'],
                'mode' => $theme['mode'],
                'palettes' => ThemePalette::values(),
                'font' => $isRtl ? 'cairo' : 'inter',
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ]);
    }

    /**
     * Scope the JSON catalog sent to the frontend: the global catalog plus
     * shared Core chrome and the dictionary of the module handling this
     * route. The full merged catalog is never serialized per response.
     *
     * @return array<string, string>
     */
    private function translationsFor(Request $request, string $locale): array
    {
        $paths = [
            lang_path("{$locale}.json"),
            module_path('Core', "lang/{$locale}.json"),
        ];

        $controller = (string) ($request->route()?->getAction('controller') ?? '');
        if (preg_match('/Modules\\\\([A-Za-z]+)\\\\/', $controller, $matches) && $matches[1] !== 'Core') {
            $paths[] = module_path($matches[1], "lang/{$locale}.json");
        }

        // Stat each candidate once; the merged catalog is cached under a key
        // that embeds the newest mtime so edits invalidate without tag support.
        $files = [];
        $mtime = 0;
        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
                $mtime = max($mtime, (int) filemtime($path));
            }
        }

        $cacheKey = 'i18n.catalog.'.md5(implode('|', $files).'.'.$mtime);

        return Cache::rememberForever($cacheKey, function () use ($files) {
            $translations = [];
            foreach ($files as $path) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    $translations = array_merge($translations, $decoded);
                }
            }

            return $translations;
        });
    }
}
