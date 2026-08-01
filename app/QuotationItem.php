<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class QuotationItem extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['qty', 'amount', 'quotation_id', 'medical_service_id', '_who_added'];

    public function medical_service()
    {
        return $this->belongsTo('App\MedicalService', 'medical_service_id');
    }


    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
