<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagnosis extends Model
{
    use SoftDeletes;

    protected $table = 'diagnoses';

    protected $fillable = [
        'diagnosis_name', 'icd_code', 'diagnosis_date', 'status',
        'severity', 'notes', 'resolved_date',
        'medical_case_id', 'patient_id', '_who_added'
    ];

    protected $dates = ['diagnosis_date', 'resolved_date'];

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
