<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_no', 'invoice_date', 'invoice_type',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'paid_amount', 'outstanding_amount', 'payment_status', 'due_date',
        'notes', 'status',
        'appointment_id', 'patient_id', 'medical_case_id', '_who_added',
        // 折扣管理字段 PRD 4.1.2
        'member_discount_rate', 'member_discount_amount',
        'item_discount_amount',
        'order_discount_rate', 'order_discount_amount',
        'coupon_id', 'coupon_discount_amount',
        'discount_approval_status', 'discount_approved_by',
        'discount_approved_at', 'discount_approval_reason',
        // 欠费挂账
        'is_credit', 'credit_approved_by', 'credit_approved_at'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'member_discount_rate' => 'decimal:2',
        'member_discount_amount' => 'decimal:2',
        'item_discount_amount' => 'decimal:2',
        'order_discount_rate' => 'decimal:2',
        'order_discount_amount' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'discount_approved_at' => 'datetime',
        'credit_approved_at' => 'datetime',
        'is_credit' => 'boolean',
    ];

    // 折扣审批阈值 (BR-035)
    const DISCOUNT_APPROVAL_THRESHOLD = 500;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-calculate outstanding amount
            $model->outstanding_amount = max(0, $model->total_amount - $model->paid_amount);

            // Auto-update payment status
            if ($model->paid_amount <= 0) {
                $model->payment_status = 'unpaid';
            } elseif ($model->paid_amount >= $model->total_amount) {
                $model->payment_status = 'paid';
            } else {
                $model->payment_status = 'partial';
            }
        });
    }

    public function appointment()
    {
        return $this->belongsTo('App\Appointment', 'appointment_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function items()
    {
        return $this->hasMany('App\InvoiceItem', 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany('App\InvoicePayment', 'invoice_id');
    }

    public function refunds()
    {
        return $this->hasMany('App\Refund', 'invoice_id');
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for partial payments
     */
    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', '!=', 'paid')
                     ->where('due_date', '<', now());
    }

    /**
     * Check if invoice is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->payment_status !== 'paid'
            && $this->due_date
            && $this->due_date->lt(now());
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals()
    {
        $this->subtotal = $this->items()->sum('amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->save();
        return $this;
    }

    /**
     * Record payment
     */
    public function recordPayment($amount)
    {
        $this->paid_amount = ($this->paid_amount ?? 0) + $amount;
        $this->save();
        return $this;
    }

    public static function InvoiceNo()
    {
        $prefix = 'SF' . date('Ymd');
        $latest = self::where('invoice_no', 'like', $prefix . '%')
            ->orderBy('invoice_no', 'desc')
            ->first();

        if (!$latest) {
            return $prefix . '0001';
        } else {
            $lastNumber = intval(substr($latest->invoice_no, -4));
            return $prefix . sprintf('%04d', $lastNumber + 1);
        }
    }

    /**
     * 优惠券关联
     */
    public function coupon()
    {
        return $this->belongsTo('App\Coupon', 'coupon_id');
    }

    /**
     * 折扣审批人
     */
    public function discountApprovedBy()
    {
        return $this->belongsTo('App\User', 'discount_approved_by');
    }

    /**
     * 挂账审批人
     */
    public function creditApprovedBy()
    {
        return $this->belongsTo('App\User', 'credit_approved_by');
    }

    /**
     * PRD 4.1.2: 计算折扣 - 按优先级顺序叠加计算
     * 优先级: 会员折扣(1) -> 项目折扣(2) -> 整单折扣(3) -> 优惠券(4)
     *
     * @param Patient|null $patient 患者(用于获取会员等级)
     * @param Coupon|null $coupon 优惠券
     * @param float $orderDiscountRate 整单折扣率
     * @param float $orderDiscountFixed 整单固定折扣
     * @return array
     */
    public function calculateDiscounts($patient = null, $coupon = null, $orderDiscountRate = 0, $orderDiscountFixed = 0)
    {
        $subtotal = $this->items()->sum(\DB::raw('price * qty'));
        $runningTotal = $subtotal;
        $discounts = [
            'subtotal' => $subtotal,
            'member_discount_rate' => 0,
            'member_discount_amount' => 0,
            'item_discount_amount' => 0,
            'order_discount_rate' => 0,
            'order_discount_amount' => 0,
            'coupon_discount_amount' => 0,
            'total_discount' => 0,
            'total_amount' => $subtotal,
        ];

        // 1. 会员折扣 (优先级1)
        if ($patient && $patient->memberLevel && $patient->memberLevel->discount_rate > 0) {
            $rate = $patient->memberLevel->discount_rate;
            $discounts['member_discount_rate'] = $rate;
            $discounts['member_discount_amount'] = round($runningTotal * ($rate / 100), 2);
            $runningTotal -= $discounts['member_discount_amount'];
        }

        // 2. 项目折扣 (优先级2) - 已在项目级别计算
        $itemDiscountTotal = $this->items()->sum('discount_amount');
        $discounts['item_discount_amount'] = $itemDiscountTotal;
        $runningTotal -= $itemDiscountTotal;

        // 3. 整单折扣 (优先级3)
        if ($orderDiscountRate > 0) {
            $discounts['order_discount_rate'] = $orderDiscountRate;
            $discounts['order_discount_amount'] = round($runningTotal * ($orderDiscountRate / 100), 2);
        } elseif ($orderDiscountFixed > 0) {
            $discounts['order_discount_amount'] = min($orderDiscountFixed, $runningTotal);
        }
        $runningTotal -= $discounts['order_discount_amount'];

        // 4. 优惠券 (优先级4)
        if ($coupon && $coupon->isValid($runningTotal, $patient ? $patient->id : null)['valid']) {
            $discounts['coupon_discount_amount'] = $coupon->calculateDiscount($runningTotal);
            $runningTotal -= $discounts['coupon_discount_amount'];
        }

        $discounts['total_discount'] = $discounts['member_discount_amount']
            + $discounts['item_discount_amount']
            + $discounts['order_discount_amount']
            + $discounts['coupon_discount_amount'];

        $discounts['total_amount'] = max(0, $runningTotal);

        return $discounts;
    }

    /**
     * 应用折扣到发票
     */
    public function applyDiscounts(array $discounts)
    {
        $this->subtotal = $discounts['subtotal'];
        $this->member_discount_rate = $discounts['member_discount_rate'];
        $this->member_discount_amount = $discounts['member_discount_amount'];
        $this->item_discount_amount = $discounts['item_discount_amount'];
        $this->order_discount_rate = $discounts['order_discount_rate'];
        $this->order_discount_amount = $discounts['order_discount_amount'];
        $this->coupon_discount_amount = $discounts['coupon_discount_amount'];
        $this->discount_amount = $discounts['total_discount'];
        $this->total_amount = $discounts['total_amount'] + ($this->tax_amount ?? 0);

        // BR-035: 折扣超过500元需要审批
        if ($discounts['total_discount'] > self::DISCOUNT_APPROVAL_THRESHOLD) {
            $this->discount_approval_status = 'pending';
        } else {
            $this->discount_approval_status = 'none';
        }

        $this->save();
        return $this;
    }

    /**
     * 检查是否需要折扣审批 (BR-035)
     */
    public function needsDiscountApproval()
    {
        return $this->discount_amount > self::DISCOUNT_APPROVAL_THRESHOLD;
    }

    /**
     * 审批折扣
     */
    public function approveDiscount($approvedBy, $reason = null)
    {
        $this->discount_approval_status = 'approved';
        $this->discount_approved_by = $approvedBy;
        $this->discount_approved_at = now();
        $this->discount_approval_reason = $reason;
        $this->save();
        return $this;
    }

    /**
     * 拒绝折扣
     */
    public function rejectDiscount($rejectedBy, $reason)
    {
        $this->discount_approval_status = 'rejected';
        $this->discount_approved_by = $rejectedBy;
        $this->discount_approved_at = now();
        $this->discount_approval_reason = $reason;
        // 重置折扣
        $this->discount_amount = 0;
        $this->member_discount_amount = 0;
        $this->item_discount_amount = 0;
        $this->order_discount_amount = 0;
        $this->coupon_discount_amount = 0;
        $this->total_amount = $this->subtotal + ($this->tax_amount ?? 0);
        $this->save();
        return $this;
    }

    /**
     * 设置为挂账
     */
    public function setAsCredit($approvedBy = null)
    {
        $this->is_credit = true;
        $this->credit_approved_by = $approvedBy;
        $this->credit_approved_at = now();
        $this->save();
        return $this;
    }

    /**
     * 检查是否可以收款
     */
    public function canAcceptPayment()
    {
        // 如果折扣需要审批且未审批通过，不允许收款
        if ($this->needsDiscountApproval() && $this->discount_approval_status !== 'approved') {
            return false;
        }
        return true;
    }

    /**
     * 待折扣审批的发票
     */
    public function scopePendingDiscountApproval($query)
    {
        return $query->where('discount_approval_status', 'pending');
    }

    /**
     * 挂账发票
     */
    public function scopeCredit($query)
    {
        return $query->where('is_credit', true);
    }

    /**
     * 获取总退款金额
     */
    public function getTotalRefundedAttribute()
    {
        return $this->refunds()->where('approval_status', 'approved')->sum('refund_amount');
    }
}
