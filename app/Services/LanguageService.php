<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LanguageService
{
    /**
     * Get supported locale codes from config.
     */
    public function getAvailableLocales(): array
    {
        $locales = config('app.available_locales', ['en' => 'English', 'zh-CN' => '简体中文']);

        // If associative array ['en' => 'English'], return keys (locale codes)
        // If indexed array ['en', 'zh-CN'], return as-is
        if ($this->isAssociativeArray($locales)) {
            return array_keys($locales);
        }

        return $locales;
    }

    /**
     * Normalize a locale code (e.g. zh -> zh-CN).
     */
    public function normalizeLocale(string $locale, array $availableLocales): string
    {
        // Already valid
        if (in_array($locale, $availableLocales)) {
            return $locale;
        }

        // Locale mapping table
        $localeMap = [
            'zh' => 'zh-CN',
            'zh-cn' => 'zh-CN',
            'zh-tw' => 'zh-TW',
            'zh-hk' => 'zh-TW',
            'en-us' => 'en',
            'en-gb' => 'en',
        ];

        $localeLower = strtolower($locale);

        if (isset($localeMap[$localeLower])) {
            $mapped = $localeMap[$localeLower];
            if (in_array($mapped, $availableLocales)) {
                Log::info("Locale mapped: {$locale} -> {$mapped}");
                return $mapped;
            }
        }

        // Prefix match (e.g. en-US matches en)
        $prefix = explode('-', $locale)[0];
        foreach ($availableLocales as $available) {
            if (strpos($available, $prefix) === 0 || $available === $prefix) {
                return $available;
            }
        }

        return $locale;
    }

    /**
     * Check whether a locale is supported.
     */
    public function isSupported(string $locale): bool
    {
        $availableLocales = $this->getAvailableLocales();
        $normalized = $this->normalizeLocale($locale, $availableLocales);

        return in_array($normalized, $availableLocales);
    }

    /**
     * Resolve a locale string to its normalized, supported form.
     * Returns null if not supported.
     */
    public function resolve(string $locale): ?string
    {
        $availableLocales = $this->getAvailableLocales();
        $normalized = $this->normalizeLocale($locale, $availableLocales);

        if (in_array($normalized, $availableLocales)) {
            return $normalized;
        }

        Log::warning('Unsupported locale: ' . $locale);
        return null;
    }

    /**
     * Check if an array is associative.
     */
    private function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
