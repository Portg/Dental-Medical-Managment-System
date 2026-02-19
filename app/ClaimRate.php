<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimRate extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = ['cash_rate', 'insurance_rate', 'status', 'doctor_id', '_who_added'];
}
