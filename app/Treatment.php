<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class Treatment extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['clinical_notes', 'treatment', 'appointment_id', '_who_added'];

    public function appointment()
    {
        return $this->belongsTo('App\Appointment', 'appointment_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
