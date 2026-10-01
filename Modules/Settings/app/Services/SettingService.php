<?php

namespace Modules\Settings\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\Locale;
use Modules\Core\Enums\ThemeMode;
use Modules\Core\Enums\ThemePalette;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\TenantSetting;
use Throwable;

class SettingService implements SettingManagerContract
{
    /**
     * Per-request memo of resolved scope maps. Settings values are identical
     * within a request; without this, every read re-hits the cache store
     * (a database SELECT when CACHE_STORE=database).
     *
     * @var array<string, array<string, mixed>>
     */
    private array $scopeMemo = [];

    /**
     * Get a setting by key and domain with landlord fallback.
     */
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed
    {
        return $this->domainMap($domain)[$key] ?? $default;
    }

    /**
     * Set a setting value. Only keys declared in the settings registry may be
     * written, and only by their owning surface (landlord/tenant/shared).
     */
    public function set(string $key, mixed $value, string $domain = 'system'): void
    {
        $this->assertWritable($domain, $key);

        $model = $this->resolveModel();
        $serialized = $model::serializeValue($value);

        $model::updateOrCreate(
            ['domain' => $domain, 'key' => $key],
            [
                'value' => $serialized['value'],
                'type' => $serialized['type'],
            ]
        );

        $this->forgetDomainMap($domain);
    }

    /**
     * Unset a setting in the current scope. Deleting the row restores
     * inheritance: tenant reads fall back to the landlord value again.
     * null is equivalent to unset — there are no stored null values.
     */
    public function unset(string $key, string $domain = 'system'): void
    {
        $this->assertWritable($domain, $key);

        $this->resolveModel()::query()
            ->where('domain', $domain)
            ->where('key', $key)
            ->delete();

        $this->forgetDomainMap($domain);
    }

    /**
     * Get all settings in a given domain as a key-value associative array.
     */
    public function allByDomain(string $domain): array
    {
        return $this->domainMap($domain);
    }

    /**
     * Definition of a writable setting from the registry, or null when the
     * key is not part of the declared settings surface.
     *
     * @return array{owner: string, type: string, rules: string}|null
     */
    public function definition(string $domain, string $key): ?array
    {
        return config("settings.definitions.{$domain}.{$key}");
    }

    /**
     * Whether the current context may write the given setting key.
     */
    public function canWrite(string $domain, string $key): bool
    {
        $definition = $this->definition($domain, $key);

        if ($definition === null) {
            return false;
        }

        return match ($definition['owner']) {
            'landlord' => ! Tenant::checkCurrent(),
            'tenant' => Tenant::checkCurrent(),
            default => true,
        };
    }

    /**
     * @throws InvalidArgumentException when the key is unregistered or owned
     *                                  by a different surface.
     */
    protected function assertWritable(string $domain, string $key): void
    {
        if ($this->definition($domain, $key) === null) {
            throw new InvalidArgumentException("Unknown setting key [{$domain}.{$key}].");
        }

        if (! $this->canWrite($domain, $key)) {
            throw new InvalidArgumentException("Setting [{$domain}.{$key}] is not writable in this context.");
        }
    }

    /**
     * Resolve the effective theme for the current context.
     *
     * Precedence: session override > tenant settings > landlord settings > defaults.
     *
     * @return array{theme: string, palette: string, mode: string}
     */
    public function getTheme(): array
    {
        $persisted = $this->domainMap('theme');

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
        ];
    }

    /**
     * Resolve branding for the current context (landlord or tenant).
     *
     * @return array<string, mixed>
     */
    public function getBranding(): array
    {
        $currentTenant = Tenant::current();
        $landlordBranding = $this->scopeMap(Setting::class, 'landlord', 'branding');

        if ($currentTenant) {
            $tenantBranding = $this->scopeMap(TenantSetting::class, 'tenant.'.$currentTenant->getKey(), 'branding');

            // tenants.name is the single source of workspace identity; a
            // media-derived logo supersedes the old logo_url setting.
            return [
                'app_name' => $currentTenant->name,
                'workspace_name' => $currentTenant->name,
                'tagline' => $tenantBranding['tagline']
                    ?? $landlordBranding['tagline']
                    ?? config('app.name'),
                'logo_url' => $currentTenant->getFirstMediaUrl('logo') ?: null,
            ];
        }

        $appName = $landlordBranding['app_name'] ?? config('app.name');

        return [
            'app_name' => $appName,
            'workspace_name' => $appName,
            'tagline' => $landlordBranding['tagline'] ?? '',
            'support_email' => $landlordBranding['support_email'] ?? null,
            'logo_url' => null,
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
     * Merged domain map for the current context: tenant values override
     * landlord values. Cached per scope and invalidated on writes only.
     *
     * @return array<string, mixed>
     */
    protected function domainMap(string $domain): array
    {
        $tenantMap = Tenant::checkCurrent()
            ? $this->scopeMap(TenantSetting::class, 'tenant.'.Tenant::current()->getKey(), $domain)
            : [];

        return array_merge($this->scopeMap(Setting::class, 'landlord', $domain), $tenantMap);
    }

    /**
     * Cached settings map for one scope (landlord or a specific tenant).
     *
     * @param  class-string<Setting|TenantSetting>  $model
     * @return array<string, mixed>
     */
    protected function scopeMap(string $model, string $scope, string $domain): array
    {
        $memoKey = "{$scope}.{$domain}";

        if (array_key_exists($memoKey, $this->scopeMemo)) {
            return $this->scopeMemo[$memoKey];
        }

        try {
            return $this->scopeMemo[$memoKey] = Cache::rememberForever("settings.map.{$memoKey}", fn () => $model::query()
                ->where('domain', $domain)
                ->get()
                ->mapWithKeys(fn ($item) => [$item->key => $item->getParsedValue()])
                ->toArray());
        } catch (Throwable) {
            return $this->scopeMemo[$memoKey] = [];
        }
    }

    /**
     * Structural invalidation: called by model saved/deleted events so every
     * write path — services, seeders, provisioners, tinker — clears the cache,
     * not only writes that happened to go through set().
     *
     * @param  class-string<Setting|TenantSetting>  $model
     */
    public static function forgetMapForModel(string $model, string $domain): void
    {
        $scope = $model === TenantSetting::class
            ? 'tenant.'.(Tenant::current()?->getKey() ?? 'unknown')
            : 'landlord';

        Cache::forget("settings.map.{$scope}.{$domain}");

        $service = app(SettingManagerContract::class);
        if ($service instanceof self) {
            $service->flushMemo();
        }
    }

    /**
     * Drop all per-request memoized maps. Writes are rare; a full flush keeps
     * invalidation correct regardless of which scope/domain was touched.
     */
    public function flushMemo(): void
    {
        $this->scopeMemo = [];
    }

    protected function forgetDomainMap(string $domain): void
    {
        $scope = Tenant::checkCurrent() ? 'tenant.'.Tenant::current()->getKey() : 'landlord';

        Cache::forget("settings.map.{$scope}.{$domain}");

        $this->flushMemo();
    }

    /**
     * Tenant-scoped map only resolves when a tenant is current.
     */
    protected function resolveModel(): string
    {
        return Tenant::checkCurrent() ? TenantSetting::class : Setting::class;
    }
}
