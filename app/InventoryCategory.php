<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'sort_order',
        'is_active',
        '_who_added',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the items for this category.
     */
    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    /**
     * Get the user who added this category.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute()
    {
        return \App\DictItem::nameByCode('inventory_category_type', $this->type) ?? $this->type;
    }

    public static function typeOptions(): array
    {
        return \App\DictItem::listByType('inventory_category_type')->pluck('name', 'code')->all();
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
