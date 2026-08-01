<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class WorkLog extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'log_date', 'patient_name', 'gender', 'age', 'visit_type',
        'phone', 'tooth_position', 'diagnosis', 'prescription',
        'doctor_id', 'doctor_name_raw', 'amount',
        'patient_id', 'invoice_id', 'medical_case_id', 'source_image', 'batch_id', '_who_added',
    ];

    protected $casts = [
        'log_date' => 'date:Y-m-d',
        'amount'   => 'decimal:2',
    ];

    const VISIT_INITIAL = 'initial';
    const VISIT_REVISIT = 'revisit';

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'invoice_id');
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
