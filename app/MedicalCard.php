<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class MedicalCard extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['card_link', 'card_type', 'patient_id', '_who_added'];

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }
}
