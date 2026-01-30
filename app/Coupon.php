<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'total_quantity',
        'used_quantity',
        'per_user_limit',
        'applicable_services',
        'applicable_member_levels',
        'start_date',
        'end_date',
        'is_active',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'applicable_services' => 'array',
        'applicable_member_levels' => 'array',
    ];

    /**
     * Check if coupon is valid for use
     */
    public function isValid($orderAmount = 0, $patientId = null)
    {
        // Check if active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => __('invoices.coupon_inactive')];
        }

        // Check date validity
        $today = now()->startOfDay();
        if ($today->lt($this->start_date) || $today->gt($this->end_date)) {
            return ['valid' => false, 'message' => __('invoices.coupon_expired')];
        }

        // Check quantity
        if ($this->total_quantity && $this->used_quantity >= $this->total_quantity) {
            return ['valid' => false, 'message' => __('invoices.coupon_exhausted')];
        }

        // Check minimum order amount
        if ($orderAmount < $this->min_order_amount) {
            return ['valid' => false, 'message' => __('invoices.coupon_min_amount', ['amount' => $this->min_order_amount])];
        }

        // Check per user limit
        if ($patientId && $this->per_user_limit > 0) {
            $usageCount = CouponUsage::where('coupon_id', $this->id)
                ->where('patient_id', $patientId)
                ->count();
            if ($usageCount >= $this->per_user_limit) {
                return ['valid' => false, 'message' => __('invoices.coupon_user_limit_reached')];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($orderAmount)
    {
        if ($this->type === 'fixed') {
            return min($this->value, $orderAmount);
        }

        // Percentage type
        $discount = $orderAmount * ($this->value / 100);
        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }
        return round($discount, 2);
    }

    /**
     * Use the coupon
     */
    public function use($patientId, $invoiceId, $discountAmount)
    {
        $this->increment('used_quantity');

        return CouponUsage::create([
            'coupon_id' => $this->id,
            'patient_id' => $patientId,
            'invoice_id' => $invoiceId,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->whereRaw('(total_quantity IS NULL OR used_quantity < total_quantity)');
    }
}