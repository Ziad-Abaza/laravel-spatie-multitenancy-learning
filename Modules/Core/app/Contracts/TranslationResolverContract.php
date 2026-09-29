<?php

namespace Modules\Core\Contracts;

interface TranslationResolverContract
{
    /**
     * Resolve and compile all global and module translations for the specified locale.
     *
     * @return array<string, string>
     */
    public function resolve(string $locale): array;

    /**
     * Register discovered module translation paths and validation attributes with Laravel's translator.
     */
    public function registerWithTranslator(): void;

    /**
     * Get translations for a specific module and locale.
     *
     * @return array<string, string>
     */
    public function getModuleTranslations(string $module, string $locale): array;

    /**
     * Get global application-wide translations for the specified locale.
     *
     * @return array<string, string>
     */
    public function getGlobalTranslations(string $locale): array;

    /**
     * Clear the compiled translation cache.
     */
    public function clearCache(): void;
}
