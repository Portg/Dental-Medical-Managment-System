<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockIn extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'stock_in_no',
        'supplier_id',
        'stock_in_date',
        'total_amount',
        'status',
        'notes',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'stock_in_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the items for this stock in.
     */
    public function items()
    {
        return $this->hasMany(StockInItem::class, 'stock_in_id');
    }

    /**
     * Get the batches created from this stock in.
     */
    public function batches()
    {
        return $this->hasMany(InventoryBatch::class, 'stock_in_id');
    }

    /**
     * Get the user who added this stock in.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Check if stock in is draft.
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if stock in is confirmed.
     */
    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if stock in is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Generate stock in number.
     */
    public static function generateStockInNo()
    {
        $prefix = 'SI';
        $date = date('Ymd');
        $lastRecord = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? intval(substr($lastRecord->stock_in_no, -4)) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and update total amount.
     */
    public function updateTotalAmount()
    {
        $this->total_amount = $this->items()->sum('amount');
        $this->save();
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => __('inventory.status_draft'),
            self::STATUS_CONFIRMED => __('inventory.status_confirmed'),
            self::STATUS_CANCELLED => __('inventory.status_cancelled'),
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
