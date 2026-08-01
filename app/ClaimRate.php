<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class ClaimRate extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = ['cash_rate', 'insurance_rate', 'status', 'doctor_id', '_who_added'];
}
