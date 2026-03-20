<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'notes',
        'business_license_no',
        'license_expiry_date',
        '_who_added',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
