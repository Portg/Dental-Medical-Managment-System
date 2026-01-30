<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'color',
        'discount_rate',
        'min_consumption',
        'points_rate',
        'benefits',
        'sort_order',
        'is_default',
        'is_active',
        '_who_added',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the members with this level.
     */
    public function members()
    {
        return $this->hasMany(Patient::class, 'member_level_id');
    }

    /**
     * Scope for active levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
