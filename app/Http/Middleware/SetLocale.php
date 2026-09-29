<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Settings\Services\SettingService;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settingService = app(SettingService::class);
        $enableArabic = (bool) $settingService->get('enable_arabic', true, 'localization');

        $supportedLocales = $enableArabic ? ['en', 'ar'] : ['en'];

        $defaultLocale = $settingService->get('default_locale', config('app.locale', 'en'), 'localization');
        if (! in_array($defaultLocale, $supportedLocales, true)) {
            $defaultLocale = 'en';
        }

        $locale = $request->get('locale')
            ?? session('locale')
            ?? $request->getPreferredLanguage($supportedLocales)
            ?? $defaultLocale;

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
