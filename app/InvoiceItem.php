<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'qty', 'price', 'discount_rate', 'discounted_price', 'actual_paid', 'arrears',
        'invoice_id', 'medical_service_id', 'tooth_no', 'doctor_id', '_who_added',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'discount_rate'    => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'actual_paid'      => 'decimal:2',
        'arrears'          => 'decimal:2',
    ];

    public function medical_service()
    {
        return $this->belongsTo('App\MedicalService', 'medical_service_id');
    }

    public function procedure_doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

}
