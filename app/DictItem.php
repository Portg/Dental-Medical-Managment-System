<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DictItem extends Model
{
    protected $fillable = ['type', 'code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    private const NAME_BY_CODE_CACHE_KEY = 'dict_items:name_by_code:%s';
    private const NAME_BY_CODE_TTL = 3600;

    /**
     * 按字典类型查询
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 获取某类型的所有活跃字典项（带缓存）
     */
    public static function listByType(string $type): \Illuminate\Support\Collection
    {
        return static::ofType($type)->active()->ordered()->get(['id', 'code', 'name']);
    }

    /**
     * 按类型与 code 获取显示名（code 与字典中一致，小写；按 type 缓存避免 N+1）
     */
    public static function nameByCode(string $type, ?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }
        $key = sprintf(self::NAME_BY_CODE_CACHE_KEY, $type);
        $map = Cache::remember($key, self::NAME_BY_CODE_TTL, function () use ($type) {
            return static::ofType($type)->active()->ordered()
                ->get(['code', 'name'])
                ->pluck('name', 'code')
                ->all();
        });
        $normalized = is_string($code) ? strtolower($code) : $code;
        return $map[$normalized] ?? $map[$code] ?? null;
    }

    /**
     * 清除某类型的 nameByCode 缓存（字典项增删改后调用）
     */
    public static function clearNameByCodeCache(string $type): void
    {
        Cache::forget(sprintf(self::NAME_BY_CODE_CACHE_KEY, $type));
    }
}
