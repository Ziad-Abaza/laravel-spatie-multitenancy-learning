<?php

namespace Modules\Settings\Services;

use App\Models\Tenant;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\TenantSetting;

class SettingService implements SettingManagerContract
{
    /**
     * Get a setting by key and domain.
     */
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed
    {
        $model = $this->resolveModel();

        try {
            $setting = $model::where('domain', $domain)
                ->where('key', $key)
                ->first();

            return $setting ? $setting->getParsedValue() : $default;
        } catch (\Throwable) {
            return $default;
        }
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
    }

    /**
     * Get all settings in a given domain as a key-value associative array.
     */
    public function allByDomain(string $domain): array
    {
        $model = $this->resolveModel();

        try {
            return $model::where('domain', $domain)
                ->get()
                ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get theme configuration.
     */
    public function getTheme(): array
    {
        $defaults = [
            'theme' => 'indigo',
            'mode' => 'dark',
            'radius' => 'rounded-xl',
        ];

        return array_merge($defaults, $this->allByDomain('theme'));
    }

    /**
     * Get branding configuration.
     */
    public function getBranding(): array
    {
        $defaults = [
            'company_name' => Tenant::current()?->name ?? 'SaaS Platform',
            'logo_url' => null,
            'tagline' => 'Next-Generation Multi-Tenant Modular Platform',
        ];

        return array_merge($defaults, $this->allByDomain('branding'));
    }

    /**
     * Resolve the active model class depending on whether a tenant is active.
     */
    protected function resolveModel(): string
    {
        return Tenant::checkCurrent() ? TenantSetting::class : Setting::class;
    }
}
