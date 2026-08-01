<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class SmsTransaction extends Model
{
    use SerializesDatesInAppTimezone;

    protected $fillable = ['amount', 'type', '_who_added'];
}
