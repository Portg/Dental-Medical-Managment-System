<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class BillingEmailNotification extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable=['email','message','item_id','notification_type','status','_who_added'];
}
