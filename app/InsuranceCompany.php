<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class InsuranceCompany extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['name', 'email', 'phone_no', '_who_added'];
}
