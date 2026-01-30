<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOutItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stock_out_id',
        'inventory_item_id',
        'qty',
        'unit_cost',
        'amount',
        'batch_no',
        '_who_added',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the stock out record.
     */
    public function stockOut()
    {
        return $this->belongsTo(StockOut::class, 'stock_out_id');
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
            $model->amount = $model->qty * $model->unit_cost;
        });

        static::saved(function ($model) {
            $model->stockOut->updateTotalAmount();
        });

        static::deleted(function ($model) {
            $model->stockOut->updateTotalAmount();
        });
    }
}
