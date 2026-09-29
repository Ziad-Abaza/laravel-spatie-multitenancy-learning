<?php

namespace Modules\Settings\Services;

use App\Models\Tenant;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\Locale;
use Modules\Core\Enums\ThemeMode;
use Modules\Core\Enums\ThemePalette;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\TenantSetting;

class SettingService implements SettingManagerContract
{
    /**
     * Get a setting by key and domain with landlord fallback.
     */
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed
    {
        if (Tenant::checkCurrent()) {
            try {
                $setting = TenantSetting::where('domain', $domain)
                    ->where('key', $key)
                    ->first();

                if ($setting !== null) {
                    return $setting->getParsedValue();
                }
            } catch (\Throwable) {
                // proceed to landlord fallback
            }
        }

        // Landlord database lookup
        try {
            $setting = Setting::where('domain', $domain)
                ->where('key', $key)
                ->first();

            if ($setting !== null) {
                return $setting->getParsedValue();
            }
        } catch (\Throwable) {
            // return default
        }

        return $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $domain = 'system', bool $isPublic = false): void
    {
        $model = $this->resolveModel();
        $serialized = $model::serializeValue($value);

        $model::updateOrCreate(
            ['domain' => $domain, 'key' => $key],
            [
                'value' => $serialized['value'],
                'type' => $serialized['type'],
                'is_public' => $isPublic,
            ]
        );

        // The tenant record's name is the canonical workspace identity.
        if (Tenant::checkCurrent() && $key === 'workspace_name' && is_string($value) && ! empty($value)) {
            Tenant::current()?->update(['name' => $value]);
        }
    }

    /**
     * Get all settings in a given domain as a key-value associative array.
     */
    public function allByDomain(string $domain): array
    {
        $landlordSettings = [];
        try {
            $landlordSettings = Setting::where('domain', $domain)
                ->get()
                ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                ->toArray();
        } catch (\Throwable) {
            $landlordSettings = [];
        }

        if (! Tenant::checkCurrent()) {
            return $landlordSettings;
        }

        try {
            $tenantSettings = TenantSetting::where('domain', $domain)
                ->get()
                ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                ->toArray();

            return array_merge($landlordSettings, $tenantSettings);
        } catch (\Throwable) {
            return $landlordSettings;
        }
    }

    /**
     * Resolve the effective theme for the current context.
     *
     * Precedence: session override > tenant settings > landlord settings > defaults.
     *
     * @return array{theme: string, palette: string, mode: string, radius: string}
     */
    public function getTheme(): array
    {
        $persisted = $this->allByDomain('theme');

        $palette = ThemePalette::tryFrom((string) session('theme', ''))
            ?? ThemePalette::tryFrom((string) ($persisted['palette'] ?? ''))
            ?? ThemePalette::Indigo;

        $mode = ThemeMode::tryFrom((string) session('theme_mode', ''))
            ?? ThemeMode::tryFrom((string) ($persisted['mode'] ?? ''))
            ?? ThemeMode::Dark;

        return [
            'theme' => $palette->value,
            'palette' => $palette->value,
            'mode' => $mode->value,
            'radius' => is_string($persisted['radius'] ?? null) ? $persisted['radius'] : 'rounded-xl',
        ];
    }

    /**
     * Resolve branding for the current context (landlord or tenant).
     */
    public function getBranding(): array
    {
        $currentTenant = Tenant::current();
        $landlordBranding = [];
        try {
            $landlordBranding = Setting::where('domain', 'branding')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                ->toArray();
        } catch (\Throwable) {
            $landlordBranding = [];
        }

        if ($currentTenant) {
            $tenantBranding = [];
            try {
                $tenantBranding = TenantSetting::where('domain', 'branding')
                    ->get()
                    ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                    ->toArray();
            } catch (\Throwable) {
                $tenantBranding = [];
            }

            $name = $tenantBranding['workspace_name'] ?? $currentTenant->name;

            return [
                'app_name' => $name,
                'workspace_name' => $name,
                'tagline' => $tenantBranding['tagline']
                    ?? $landlordBranding['tagline']
                    ?? 'Next-Generation Multi-Tenant Modular Platform',
                'logo_url' => $tenantBranding['logo_url'] ?? null,
            ];
        }

        $appName = $landlordBranding['app_name'] ?? 'SaaS Cloud';

        return [
            'app_name' => $appName,
            'workspace_name' => $appName,
            'tagline' => $landlordBranding['tagline'] ?? 'Multi-Tenant Enterprise Architecture',
            'support_email' => $landlordBranding['support_email'] ?? 'support@saas.test',
            'logo_url' => $landlordBranding['logo_url'] ?? null,
        ];
    }

    /**
     * Supported locales keyed by code, e.g. ['en' => 'English', 'ar' => 'العربية'].
     */
    public function supportedLocales(): array
    {
        $configured = $this->get('supported_locales', null, 'localization');
        $configured = is_array($configured) ? $configured : [Locale::English->value, Locale::Arabic->value];

        $supported = [];
        foreach (Locale::cases() as $locale) {
            if (in_array($locale->value, $configured, true)) {
                $supported[$locale->value] = $locale->label();
            }
        }

        return $supported !== [] ? $supported : [Locale::English->value => Locale::English->label()];
    }

    /**
     * The configured default locale, guaranteed to be a supported one.
     */
    public function defaultLocale(): string
    {
        $default = $this->get('default_locale', config('app.locale', Locale::English->value), 'localization');

        return array_key_exists($default, $this->supportedLocales())
            ? $default
            : Locale::English->value;
    }

    /**
     * Resolve the active model class depending on whether a tenant is active.
     */
    protected function resolveModel(): string
    {
        return Tenant::checkCurrent() ? TenantSetting::class : Setting::class;
    }
}
