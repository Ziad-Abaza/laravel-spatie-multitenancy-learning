<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\SettingManagerContract;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(SettingManagerContract::class);

        $supportedLocales = array_keys($settings->supportedLocales());
        $defaultLocale = $settings->defaultLocale();

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
