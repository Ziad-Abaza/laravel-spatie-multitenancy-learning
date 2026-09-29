<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\File;
use Modules\Core\Contracts\TranslationResolverContract;
use Nwidart\Modules\Facades\Module;

class TranslationResolver implements TranslationResolverContract
{
    /**
     * In-memory cache of resolved translations per locale.
     *
     * @var array<string, array<string, string>>
     */
    protected array $resolvedCache = [];

    /**
     * Resolve and compile all global and module translations for the specified locale.
     *
     * @return array<string, string>
     */
    public function resolve(string $locale): array
    {
        if (isset($this->resolvedCache[$locale])) {
            return $this->resolvedCache[$locale];
        }

        $translations = $this->getGlobalTranslations($locale);

        $moduleNames = $this->getEnabledModuleNames();

        foreach ($moduleNames as $moduleName) {
            $moduleTranslations = $this->getModuleTranslations($moduleName, $locale);
            $moduleLower = strtolower($moduleName);

            foreach ($moduleTranslations as $key => $value) {
                // Flat key lookup
                $translations[$key] = $value;

                // Namespaced aliases to avoid collisions and allow explicit module scoping
                $translations["{$moduleLower}::{$key}"] = $value;
                $translations["{$moduleLower}.{$key}"] = $value;
            }
        }

        $this->resolvedCache[$locale] = $translations;

        return $translations;
    }

    /**
     * Register discovered module translation paths and validation attributes with Laravel's translator.
     */
    public function registerWithTranslator(): void
    {
        $translator = app('translator');
        $moduleNames = $this->getEnabledModuleNames();
        $locales = ['en', 'ar'];

        foreach ($moduleNames as $moduleName) {
            $moduleLower = strtolower($moduleName);
            $langDir = $this->findModuleLangDirectory($moduleName);

            if (! $langDir || ! is_dir($langDir)) {
                continue;
            }

            // Register JSON translations path and namespace
            $translator->addJsonPath($langDir);
            $translator->addNamespace($moduleLower, $langDir);

            // Register module validation attributes & messages
            foreach ($locales as $loc) {
                $validationFile = $langDir.DIRECTORY_SEPARATOR.$loc.DIRECTORY_SEPARATOR.'validation.php';
                if (file_exists($validationFile)) {
                    // Pre-load base validation group so addLines merges into it without replacing base rules
                    $translator->load('*', 'validation', $loc);

                    $validationData = require $validationFile;
                    if (is_array($validationData)) {
                        if (isset($validationData['attributes']) && is_array($validationData['attributes'])) {
                            foreach ($validationData['attributes'] as $attribute => $label) {
                                $translator->addLines(["validation.attributes.{$attribute}" => $label], $loc);
                            }
                        }
                        if (isset($validationData['custom']) && is_array($validationData['custom'])) {
                            foreach ($validationData['custom'] as $customKey => $customVal) {
                                if (is_array($customVal)) {
                                    foreach ($customVal as $rule => $msg) {
                                        $translator->addLines(["validation.custom.{$customKey}.{$rule}" => $msg], $loc);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get translations for a specific module and locale.
     *
     * @return array<string, string>
     */
    public function getModuleTranslations(string $module, string $locale): array
    {
        $translations = [];
        $langDir = $this->findModuleLangDirectory($module);

        if (! $langDir || ! is_dir($langDir)) {
            return [];
        }

        // 1. Direct JSON file: {locale}.json
        $directJsonPath = $langDir.DIRECTORY_SEPARATOR."{$locale}.json";
        if (file_exists($directJsonPath)) {
            $decoded = json_decode(file_get_contents($directJsonPath), true);
            if (is_array($decoded)) {
                $translations = array_merge($translations, $decoded);
            }
        }

        // 2. Sub-directory JSON files: {locale}/*.json
        $localeDir = $langDir.DIRECTORY_SEPARATOR.$locale;
        if (is_dir($localeDir)) {
            $jsonFiles = glob($localeDir.DIRECTORY_SEPARATOR.'*.json') ?: [];
            foreach ($jsonFiles as $file) {
                $decoded = json_decode(file_get_contents($file), true);
                if (is_array($decoded)) {
                    $translations = array_merge($translations, $decoded);
                }
            }

            // 3. Sub-directory PHP files: {locale}/*.php
            $phpFiles = glob($localeDir.DIRECTORY_SEPARATOR.'*.php') ?: [];
            foreach ($phpFiles as $file) {
                $baseName = basename($file, '.php');
                if ($baseName === 'validation') {
                    // Handled specifically for validation attributes
                    continue;
                }
                $data = require $file;
                if (is_array($data)) {
                    foreach ($data as $k => $v) {
                        if (is_string($v)) {
                            $translations["{$baseName}.{$k}"] = $v;
                        }
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Get global application-wide translations for the specified locale.
     *
     * @return array<string, string>
     */
    public function getGlobalTranslations(string $locale): array
    {
        $paths = [
            resource_path("lang/{$locale}.json"),
            base_path("lang/{$locale}.json"),
        ];

        $translations = [];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path), true);
                if (is_array($decoded)) {
                    $translations = array_merge($translations, $decoded);
                }
            }
        }

        return $translations;
    }

    /**
     * Clear the compiled translation cache.
     */
    public function clearCache(): void
    {
        $this->resolvedCache = [];
    }

    /**
     * Get list of enabled module names.
     *
     * @return array<string>
     */
    protected function getEnabledModuleNames(): array
    {
        try {
            if (class_exists(Module::class)) {
                $modules = Module::allEnabled();
                if (! empty($modules)) {
                    return array_map(fn ($mod) => $mod->getName(), array_values($modules));
                }
            }
        } catch (\Throwable) {
            // Fallback to directory scan
        }

        $modulesPath = base_path('Modules');
        if (! is_dir($modulesPath)) {
            return [];
        }

        $names = [];
        $items = scandir($modulesPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (is_dir($modulesPath.DIRECTORY_SEPARATOR.$item)) {
                $names[] = $item;
            }
        }

        return $names;
    }

    /**
     * Locate the translation directory for a given module.
     */
    protected function findModuleLangDirectory(string $module): ?string
    {
        // 1. Standard module lang directory: Modules/{Module}/lang
        $standardPath = base_path("Modules/{$module}/lang");
        if (is_dir($standardPath)) {
            return $standardPath;
        }

        // 2. Fallback module resources/lang: Modules/{Module}/resources/lang
        $resourcesPath = base_path("Modules/{$module}/resources/lang");
        if (is_dir($resourcesPath)) {
            return $resourcesPath;
        }

        return null;
    }
}
