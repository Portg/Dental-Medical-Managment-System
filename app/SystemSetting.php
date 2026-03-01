<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    private const CACHE_KEY = 'system_settings:all';
    private const CACHE_TTL = 86400;

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getAllCached();

        if (!isset($settings[$key])) {
            return $default;
        }

        return self::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get all settings as key-value pairs.
     */
    public static function getAll(): array
    {
        $settings = self::getAllCached();
        $result = [];

        foreach ($settings as $key => $item) {
            $result[$key] = self::castValue($item['value'], $item['type']);
        }

        return $result;
    }

    /**
     * Get all settings for a specific group.
     */
    public static function getGroup(string $group): array
    {
        $all = self::getAll();
        $prefix = $group . '.';
        $result = [];

        foreach ($all as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $shortKey = substr($key, strlen($prefix));
                $result[$shortKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Bulk update multiple settings at once.
     */
    public static function setMany(array $keyValues): void
    {
        foreach ($keyValues as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get all settings from cache.
     */
    private static function getAllCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::all()
                ->keyBy('key')
                ->map(fn ($item) => ['value' => $item->value, 'type' => $item->type])
                ->toArray();
        });
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(?string $value, string $type)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
