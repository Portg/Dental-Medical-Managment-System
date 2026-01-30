<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subjective', 'objective', 'assessment', 'plan',
        'note_date', 'note_type',
        'appointment_id', 'medical_case_id', 'patient_id', '_who_added'
    ];

    protected $dates = ['note_date'];

    public function appointment()
    {
        return $this->belongsTo('App\Appointment', 'appointment_id');
    }

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
