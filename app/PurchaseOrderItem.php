<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'total_price',
        'batch_no',
        'expiry_date',
        '_who_added',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_price = $model->quantity_ordered * $model->unit_price;
        });

        static::saved(function ($model) {
            $model->purchaseOrder->updateTotal();
        });

        static::deleted(function ($model) {
            $model->purchaseOrder->updateTotal();
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function isFullyReceived()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function getPendingQuantityAttribute()
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}
