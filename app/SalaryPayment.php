<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class SalaryPayment extends Model
{
    use SerializesDatesInAppTimezone;

    protected $guarded = ['id'];
}
