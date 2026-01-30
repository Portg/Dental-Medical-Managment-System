<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'batch_no',
        'expiry_date',
        'production_date',
        'qty',
        'unit_cost',
        'stock_in_id',
        'status',
        '_who_added',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'production_date' => 'date',
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the stock in record.
     */
    public function stockIn()
    {
        return $this->belongsTo(StockIn::class, 'stock_in_id');
    }

    /**
     * Get the user who added this batch.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Get days to expiry.
     */
    public function getDaysToExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if batch is near expiry (within 30 days by default).
     */
    public function isNearExpiry($days = 30)
    {
        if (!$this->expiry_date) {
            return false;
        }
        $daysToExpiry = $this->days_to_expiry;
        return $daysToExpiry !== null && $daysToExpiry >= 0 && $daysToExpiry <= $days;
    }

    /**
     * Scope to get available batches.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('qty', '>', 0);
    }

    /**
     * Scope to get expired batches.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', Carbon::now());
              });
        });
    }

    /**
     * Scope to get near expiry batches.
     */
    public function scopeNearExpiry($query, $days = 30)
    {
        return $query->where('status', 'available')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to order by FIFO (First In, First Out).
     */
    public function scopeFifo($query)
    {
        return $query->orderBy('expiry_date')->orderBy('created_at');
    }

    /**
     * Deduct quantity from batch.
     */
    public function deductQty($amount)
    {
        $this->qty -= $amount;
        if ($this->qty <= 0) {
            $this->qty = 0;
            $this->status = 'depleted';
        }
        $this->save();
    }
}
