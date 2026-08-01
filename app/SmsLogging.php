<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class SmsLogging extends Model
{
    use SerializesDatesInAppTimezone;

    protected $fillable = ['phone_number', 'message','type', 'cost', 'status'];
}
