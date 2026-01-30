<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_code',
        'name',
        'specification',
        'unit',
        'category_id',
        'brand',
        'reference_price',
        'selling_price',
        'track_expiry',
        'stock_warning_level',
        'storage_location',
        'current_stock',
        'average_cost',
        'notes',
        'is_active',
        '_who_added',
    ];

    protected $casts = [
        'reference_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'track_expiry' => 'boolean',
        'is_active' => 'boolean',
        'stock_warning_level' => 'integer',
    ];

    /**
     * Get the category of this item.
     */
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * Get the batches for this item.
     */
    public function batches()
    {
        return $this->hasMany(InventoryBatch::class, 'inventory_item_id');
    }

    /**
     * Get available batches (not expired, not depleted).
     */
    public function availableBatches()
    {
        return $this->batches()->where('status', 'available')->where('qty', '>', 0);
    }

    /**
     * Get the stock in items.
     */
    public function stockInItems()
    {
        return $this->hasMany(StockInItem::class, 'inventory_item_id');
    }

    /**
     * Get the stock out items.
     */
    public function stockOutItems()
    {
        return $this->hasMany(StockOutItem::class, 'inventory_item_id');
    }

    /**
     * Get the service consumables.
     */
    public function serviceConsumables()
    {
        return $this->hasMany(ServiceConsumable::class, 'inventory_item_id');
    }

    /**
     * Get the user who added this item.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Check if stock is below warning level.
     */
    public function isLowStock()
    {
        return $this->current_stock <= $this->stock_warning_level;
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get items with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= stock_warning_level');
    }

    /**
     * Scope to search items.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('item_code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%");
        });
    }
}
