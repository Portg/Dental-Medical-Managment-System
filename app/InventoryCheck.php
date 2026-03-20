<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCheck extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT     = 'draft';
    const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'check_no',
        'category_id',
        'check_date',
        'status',
        'notes',
        'checked_by',
        'confirmed_at',
        '_who_added',
    ];

    protected $casts = [
        'check_date'   => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * 所属分类。
     */
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * 盘点明细。
     */
    public function items()
    {
        return $this->hasMany(InventoryCheckItem::class, 'inventory_check_id');
    }

    /**
     * 创建人。
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * 盘点确认人。
     */
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * 是否草稿。
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * 是否已确认。
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * 生成盘点单号：IC + Ymd + 4位序号。
     */
    public static function generateCheckNo(): string
    {
        $prefix = 'IC';
        $date   = date('Ymd');
        $last   = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $last ? intval(substr($last->check_no, -4)) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取状态标签。
     */
    public function getStatusLabelAttribute(): string
    {
        return DictItem::nameByCode('check_status', $this->status) ?? $this->status;
    }
}
