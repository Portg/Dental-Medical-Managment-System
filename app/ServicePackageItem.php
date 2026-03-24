<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicePackageItem extends Model
{
    protected $fillable = ['package_id', 'service_id', 'qty', 'price', 'sort_order'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(MedicalService::class, 'service_id');
    }
}
