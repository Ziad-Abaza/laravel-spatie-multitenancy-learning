<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\Locale;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ErrorPageRenderer
{
    /**
     * Render the unified Inertia error page for any HTML error response.
     */
    public function respond(Response $response, Throwable $e, Request $request)
    {
        if ($response->getStatusCode() < 400 || $request->expectsJson() || $request->is('api/*')) {
            return $response;
        }

        try {
            $status = $e instanceof NoCurrentTenant ? 404 : $response->getStatusCode();
            $hasTenant = Tenant::current() !== null;

            return Inertia::render('Core/ErrorPage', [
                'status' => $status,
                'message' => $e instanceof NoCurrentTenant ? __('error_tenant_not_found') : null,
                'exception' => config('app.debug') ? $e->getMessage() : null,
                'loginUrl' => $hasTenant ? '/login' : '/landlord/login',
                'homeUrl' => $hasTenant ? '/' : (rtrim((string) config('app.url', '/'), '/') ?: '/'),
                ...$this->localeProps($request),
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable) {
            return $response;
        }
    }

    /**
     * Shared locale props only exist when the web middleware stack ran. On
     * earlier failures (e.g. route misses) they must be rebuilt manually so
     * the error page can still translate and render RTL correctly.
     *
     * @return array{locale?: array{current: string, is_rtl: bool, supported: array<string, string>, translations: array<string, string>}}
     */
    protected function localeProps(Request $request): array
    {
        $shared = Inertia::getShared('locale');

        if (is_array($shared) && ! empty($shared['translations'])) {
            return [];
        }

        $locale = is_array($shared) ? ($shared['current'] ?? null) : null;
        $locale = is_string($locale) ? $locale : $this->resolveLocale($request);
        app()->setLocale($locale);

        return [
            'locale' => [
                'current' => $locale,
                'is_rtl' => Locale::tryFrom($locale)?->isRtl() ?? false,
                'supported' => $this->supportedLocales(),
                'translations' => app('translator')->getLoader()->load($locale, '*', '*'),
            ],
        ];
    }

    protected function resolveLocale(Request $request): string
    {
        $supported = array_keys($this->supportedLocales());
        $default = config('app.locale', Locale::English->value);

        try {
            $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        } catch (Throwable) {
            $sessionLocale = null;
        }

        $locale = $request->query('locale')
            ?? (is_string($sessionLocale) ? $sessionLocale : null)
            ?? $request->getPreferredLanguage($supported)
            ?? $default;

        return in_array($locale, $supported, true) ? $locale : $default;
    }

    /**
     * @return array<string, string>
     */
    protected function supportedLocales(): array
    {
        try {
            return app(SettingManagerContract::class)->supportedLocales();
        } catch (Throwable) {
            return collect(Locale::cases())
                ->mapWithKeys(fn (Locale $locale) => [$locale->value => $locale->label()])
                ->all();
        }
    }
}
