<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class MedicalCardItem extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['card_photo', 'medical_card_id', '_who_added'];
}
