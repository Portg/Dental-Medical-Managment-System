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
        $types = [
            'drug' => __('inventory.type_drug'),
            'consumable' => __('inventory.type_consumable'),
            'instrument' => __('inventory.type_instrument'),
            'dental_material' => __('inventory.type_dental_material'),
            'office' => __('inventory.type_office'),
        ];

        return $types[$this->type] ?? $this->type;
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
