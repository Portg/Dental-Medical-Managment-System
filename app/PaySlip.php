<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class PaySlip extends Model
{
    use SerializesDatesInAppTimezone;

    Use SoftDeletes;
    protected $fillable = ['payslip_month','employee_id','employee_contract_id','_who_added'];
}
