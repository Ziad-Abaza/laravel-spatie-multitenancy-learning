<?php

namespace Modules\Settings\Services;

use App\Models\Tenant;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\TenantSetting;

class SettingService implements SettingManagerContract
{
    /**
     * Get a setting by key and domain with landlord fallback.
     */
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed
    {
        // Aliases mapping for unified settings access
        $normalizedKey = $this->normalizeKey($key);

        if (Tenant::checkCurrent()) {
            try {
                $setting = TenantSetting::where('domain', $domain)
                    ->where(function ($q) use ($key, $normalizedKey) {
                        $q->where('key', $key)->orWhere('key', $normalizedKey);
                    })
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
                ->where(function ($q) use ($key, $normalizedKey) {
                    $q->where('key', $key)->orWhere('key', $normalizedKey);
                })
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

        // Keep alias in sync
        $alias = $this->getAliasKey($key);
        if ($alias !== null) {
            $model::updateOrCreate(
                ['domain' => $domain, 'key' => $alias],
                [
                    'value' => $serialized['value'],
                    'type' => $serialized['type'],
                    'is_public' => $isPublic,
                ]
            );
        }

        // If in tenant context and updating company_name or workspace_name, sync tenant record
        if (Tenant::checkCurrent() && in_array($key, ['company_name', 'workspace_name'], true) && is_string($value) && ! empty($value)) {
            $currentTenant = Tenant::current();
            if ($currentTenant) {
                $currentTenant->name = $value;
                $currentTenant->save();
            }
        }

        // If in tenant context and updating theme or mode, sync tenant settings json column
        if (Tenant::checkCurrent() && $domain === 'theme') {
            $currentTenant = Tenant::current();
            if ($currentTenant) {
                $existing = $currentTenant->settings ?? [];
                $existing[$key] = $value;
                $currentTenant->settings = $existing;
                $currentTenant->save();
            }
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
     * Get theme configuration.
     */
    public function getTheme(): array
    {
        $defaults = [
            'theme' => 'indigo',
            'palette' => 'indigo',
            'mode' => 'dark',
            'radius' => 'rounded-xl',
        ];

        $domainSettings = $this->allByDomain('theme');

        $theme = $domainSettings['theme']
            ?? $domainSettings['palette']
            ?? $domainSettings['default_palette']
            ?? $domainSettings['default_theme']
            ?? $defaults['theme'];

        $mode = $domainSettings['mode']
            ?? $domainSettings['default_mode']
            ?? $defaults['mode'];

        return [
            'theme' => $theme,
            'palette' => $theme,
            'default_palette' => $theme,
            'mode' => $mode,
            'default_mode' => $mode,
            'radius' => $domainSettings['radius'] ?? $defaults['radius'],
        ];
    }

    /**
     * Get branding configuration.
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

            $name = $tenantBranding['workspace_name']
                ?? $tenantBranding['company_name']
                ?? $currentTenant->name
                ?? 'Workspace';

            $tagline = $tenantBranding['tagline']
                ?? $landlordBranding['tagline']
                ?? 'Next-Generation Multi-Tenant Modular Platform';

            return [
                'company_name' => $name,
                'workspace_name' => $name,
                'tagline' => $tagline,
                'logo_url' => $tenantBranding['logo_url'] ?? null,
                'app_name' => $name,
            ];
        }

        $appName = $landlordBranding['app_name'] ?? 'SaaS Cloud';
        $tagline = $landlordBranding['tagline'] ?? 'Multi-Tenant Enterprise Architecture';
        $supportEmail = $landlordBranding['support_email'] ?? 'support@saas.test';

        return [
            'app_name' => $appName,
            'company_name' => $appName,
            'workspace_name' => $appName,
            'tagline' => $tagline,
            'support_email' => $supportEmail,
            'logo_url' => $landlordBranding['logo_url'] ?? null,
        ];
    }

    /**
     * Resolve the active model class depending on whether a tenant is active.
     */
    protected function resolveModel(): string
    {
        return Tenant::checkCurrent() ? TenantSetting::class : Setting::class;
    }

    /**
     * Normalize settings keys between different conventions.
     */
    protected function normalizeKey(string $key): string
    {
        return match ($key) {
            'default_palette', 'default_theme', 'palette' => 'theme',
            'default_mode' => 'mode',
            'registration_enabled' => 'allow_registration',
            'allow_registration' => 'registration_enabled',
            'workspace_name' => 'company_name',
            'company_name' => 'workspace_name',
            default => $key,
        };
    }

    /**
     * Return alias key for synchronization if applicable.
     */
    protected function getAliasKey(string $key): ?string
    {
        return match ($key) {
            'default_palette' => 'theme',
            'palette' => 'theme',
            'theme' => 'default_palette',
            'default_mode' => 'mode',
            'mode' => 'default_mode',
            'registration_enabled' => 'allow_registration',
            'allow_registration' => 'registration_enabled',
            'workspace_name' => 'company_name',
            'company_name' => 'workspace_name',
            default => null,
        };
    }
}
