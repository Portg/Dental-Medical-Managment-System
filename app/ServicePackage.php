<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'total_price', 'is_active', '_who_added'];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_price' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(ServicePackageItem::class, 'package_id')->orderBy('sort_order');
    }
}
