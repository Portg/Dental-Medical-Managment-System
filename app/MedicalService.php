<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class MedicalService extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'unit', 'price', 'category', 'description', 'is_active', 'is_prescription', '_who_added'];

    protected $casts = [
        'is_prescription' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Scope: 处方类项目
     */
    public function scopePrescription($query)
    {
        return $query->where('is_prescription', true);
    }

    protected static function booted(): void
    {
        $clearCache = function () {
            Cache::forget('billing_service_category_tree');
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }
}
