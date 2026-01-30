<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'refund_no',
        'invoice_id',
        'patient_id',
        'refund_amount',
        'refund_reason',
        'refund_date',
        'refund_method',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'refund_date' => 'datetime',
        'approved_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    public static function generateRefundNo()
    {
        $prefix = 'TK' . date('Ymd');
        $lastRefund = static::where('refund_no', 'like', $prefix . '%')
            ->orderBy('refund_no', 'desc')
            ->first();

        if ($lastRefund) {
            $lastNum = intval(substr($lastRefund->refund_no, -4));
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $prefix . $newNum;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function needsApproval()
    {
        return $this->refund_amount > 100;
    }
}
