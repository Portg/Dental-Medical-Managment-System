<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

class TranslationService
{
    /**
     * Validate and return a valid locale code.
     */
    public function validateLocale(?string $locale): string
    {
        $locale = $locale ?: App::getLocale();

        $availableLocales = config('app.available_locales', ['en' => 'English', 'zh-CN' => '简体中文']);

        // Handle associative arrays
        if (is_array($availableLocales) && !isset($availableLocales[0])) {
            $availableLocales = array_keys($availableLocales);
        }

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'zh-CN');
        }

        return $locale;
    }

    /**
     * Convert URL format to Laravel namespace format.
     *
     * vendor/package/messages -> vendor/package::messages
     * package/messages        -> package::messages
     * patient                 -> patient
     */
    public function convertToLaravelKey(string $urlPath): string
    {
        // If already contains ::, return as-is
        if (strpos($urlPath, '::') !== false) {
            return $urlPath;
        }

        // If contains /, convert to namespace format
        if (strpos($urlPath, '/') !== false) {
            $lastSlash = strrpos($urlPath, '/');
            $namespace = substr($urlPath, 0, $lastSlash);
            $module = substr($urlPath, $lastSlash + 1);

            return $namespace . '::' . $module;
        }

        return $urlPath;
    }

    /**
     * Get translations for a single module.
     */
    public function getModuleTranslations(string $module, string $locale): array
    {
        App::setLocale($locale);

        $laravelKey = $this->convertToLaravelKey($module);
        $translations = Lang::get($laravelKey);

        // If translation doesn't exist (returns original key), return empty array
        if ($translations === $laravelKey) {
            $translations = [];
        }

        return [
            'locale' => $locale,
            'module' => $laravelKey,
            'translations' => $translations,
        ];
    }

    /**
     * Get translations for multiple modules.
     */
    public function getMultipleModuleTranslations(string $modules, string $locale): array
    {
        App::setLocale($locale);

        $moduleList = array_filter(array_map('trim', explode(',', $modules)));

        $result = [];
        foreach ($moduleList as $module) {
            $laravelKey = $this->convertToLaravelKey($module);
            $translations = Lang::get($laravelKey);

            if ($translations !== $laravelKey) {
                $result[$laravelKey] = $translations;
            }
        }

        return [
            'locale' => $locale,
            'translations' => $result,
        ];
    }

    /**
     * List all available translation modules for a locale.
     */
    public function listAvailableModules(string $locale): array
    {
        $modules = [];

        // 1. Standard language files
        $langPath = resource_path('lang/' . $locale);
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $modules[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        // 2. Vendor namespace modules
        $vendorPath = resource_path('lang/vendor');
        if (File::isDirectory($vendorPath)) {
            $packages = File::directories($vendorPath);
            foreach ($packages as $packagePath) {
                $packageName = basename($packagePath);
                $packageLangPath = $packagePath . '/' . $locale;

                if (File::isDirectory($packageLangPath)) {
                    $files = File::files($packageLangPath);
                    foreach ($files as $file) {
                        if ($file->getExtension() === 'php') {
                            $moduleName = $file->getFilenameWithoutExtension();
                            $modules[] = $packageName . '::' . $moduleName;
                        }
                    }
                }
            }
        }

        return [
            'locale' => $locale,
            'modules' => $modules,
        ];
    }

    /**
     * Get all translations for a locale (including vendor namespaces).
     */
    public function getAllTranslations(string $locale): array
    {
        App::setLocale($locale);

        $allTranslations = [];

        // 1. Load standard language files
        $langPath = resource_path('lang/' . $locale);
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $module = $file->getFilenameWithoutExtension();
                    $translations = Lang::get($module);
                    if ($translations !== $module) {
                        $allTranslations[$module] = $translations;
                    }
                }
            }
        }

        // 2. Load vendor namespace modules
        $vendorPath = resource_path('lang/vendor');
        if (File::isDirectory($vendorPath)) {
            $packages = File::directories($vendorPath);
            foreach ($packages as $packagePath) {
                $packageName = basename($packagePath);
                $packageLangPath = $packagePath . '/' . $locale;

                if (File::isDirectory($packageLangPath)) {
                    $files = File::files($packageLangPath);
                    foreach ($files as $file) {
                        if ($file->getExtension() === 'php') {
                            $moduleName = $file->getFilenameWithoutExtension();
                            $fullKey = $packageName . '::' . $moduleName;
                            $translations = Lang::get($fullKey);
                            if ($translations !== $fullKey) {
                                $allTranslations[$fullKey] = $translations;
                            }
                        }
                    }
                }
            }
        }

        return [
            'locale' => $locale,
            'translations' => $allTranslations,
        ];
    }
}
