<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VitalSign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'blood_pressure_systolic', 'blood_pressure_diastolic',
        'heart_rate', 'temperature', 'respiratory_rate', 'oxygen_saturation',
        'weight', 'height', 'notes', 'recorded_at',
        'appointment_id', 'patient_id', '_who_added'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo('App\Appointment', 'appointment_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function getBloodPressureAttribute()
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return $this->blood_pressure_systolic . '/' . $this->blood_pressure_diastolic;
        }
        return null;
    }

    public function getBmiAttribute()
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }
}
