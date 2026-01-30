<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockInItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stock_in_id',
        'inventory_item_id',
        'qty',
        'unit_price',
        'amount',
        'batch_no',
        'expiry_date',
        'production_date',
        '_who_added',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'expiry_date' => 'date',
        'production_date' => 'date',
    ];

    /**
     * Get the stock in record.
     */
    public function stockIn()
    {
        return $this->belongsTo(StockIn::class, 'stock_in_id');
    }

    /**
     * Get the inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the user who added this item.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Calculate amount before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->amount = $model->qty * $model->unit_price;
        });

        static::saved(function ($model) {
            $model->stockIn->updateTotalAmount();
        });

        static::deleted(function ($model) {
            $model->stockIn->updateTotalAmount();
        });
    }

    /**
     * Check price deviation from reference price.
     */
    public function getPriceDeviationAttribute()
    {
        $item = $this->inventoryItem;
        if (!$item || $item->reference_price == 0) {
            return 0;
        }

        return abs($this->unit_price - $item->reference_price) / $item->reference_price;
    }

    /**
     * Check if price deviation exceeds threshold.
     */
    public function hasPriceDeviation($threshold = 0.2)
    {
        return $this->price_deviation > $threshold;
    }
}
