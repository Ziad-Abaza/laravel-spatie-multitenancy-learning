<?php

namespace Modules\Core\Contracts;

interface SettingManagerContract
{
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed;

    public function set(string $key, mixed $value, string $domain = 'system'): void;

    /**
     * Remove a setting row in the current scope, restoring landlord
     * inheritance for tenant-owned keys.
     */
    public function unset(string $key, string $domain = 'system'): void;

    /**
     * @return array<string, mixed>
     */
    public function allByDomain(string $domain): array;

    /**
     * Resolve the effective theme for the current context.
     *
     * Precedence: session override > tenant settings > landlord settings > defaults.
     *
     * @return array{theme: string, palette: string, mode: string}
     */
    public function getTheme(): array;

    /**
     * Resolve branding for the current context (landlord or tenant).
     *
     * @return array<string, mixed>
     */
    public function getBranding(): array;

    /**
     * Supported locales keyed by code, e.g. ['en' => 'English', 'ar' => 'العربية'].
     *
     * Driven by the `localization.supported_locales` setting.
     *
     * @return array<string, string>
     */
    public function supportedLocales(): array;

    /**
     * The configured default locale, guaranteed to be a supported one.
     */
    public function defaultLocale(): string;
}
