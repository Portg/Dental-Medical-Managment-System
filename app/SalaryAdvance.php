<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class SalaryAdvance extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['payment_classification', 'employee_id', 'advance_month',
        'advance_amount', 'payment_method', 'payment_date', '_who_added'];
}
