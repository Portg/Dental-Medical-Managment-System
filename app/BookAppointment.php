<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class BookAppointment extends Model
{
    use SerializesDatesInAppTimezone;

    protected  $fillable=['full_name','phone_number','email','message','status'];
}
