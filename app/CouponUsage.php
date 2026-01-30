<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'patient_id',
        'invoice_id',
        'discount_amount',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}