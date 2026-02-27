<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DictItem extends Model
{
    protected $fillable = ['type', 'code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
}
