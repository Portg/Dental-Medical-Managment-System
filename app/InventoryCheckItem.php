<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCheckItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inventory_check_id',
        'inventory_item_id',
        'system_qty',
        'actual_qty',
        'diff_qty',
        '_who_added',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'actual_qty' => 'decimal:2',
        'diff_qty'   => 'decimal:2',
    ];

    /**
     * 所属盘点单。
     */
    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    /**
     * 对应库存物品。
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * 偏差率：|diff_qty| / system_qty，system_qty=0 时返回 0。
     * AG-065：使用 bcmath 计算。
     */
    public function getDeviationRateAttribute(): float
    {
        if (is_null($this->diff_qty) || is_null($this->system_qty)) {
            return 0.0;
        }

        $systemQty = (string) $this->system_qty;
        if (bccomp($systemQty, '0', 4) === 0) {
            return 0.0;
        }

        $absDiff = bcsub((string) $this->actual_qty, $systemQty, 4);
        if (bccomp($absDiff, '0', 4) < 0) {
            $absDiff = bcsub('0', $absDiff, 4);
        }

        return (float) bcdiv($absDiff, $systemQty, 6);
    }
}
