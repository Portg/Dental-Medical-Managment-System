<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class DoctorClaim extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    const STATUS_PENDING = 'Pending';
    const STATUS_APPROVED = 'Approved';

    protected $fillable = ['claim_amount', 'insurance_amount', 'cash_amount', 'claim_rate_id', 'appointment_id', '_who_added'];
}
