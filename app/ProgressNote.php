<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ProgressNote extends Model implements AuditableContract
{
    use SoftDeletes, Auditable;

    protected $auditExclude = ['updated_at', 'created_at'];

    public function generateTags(): array
    {
        return ['medical-record'];
    }

    protected $fillable = [
        'subjective', 'objective', 'assessment', 'plan',
        'note_date', 'note_type',
        'appointment_id', 'medical_case_id', 'patient_id', '_who_added'
    ];

    protected $casts = [
        'note_date' => 'datetime',
    ];

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
