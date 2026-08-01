<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class BirthDayMessage extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable=['message','_who_added'];
}
