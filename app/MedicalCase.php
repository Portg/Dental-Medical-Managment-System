<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalCase extends Model
{
    use SoftDeletes;

    const STATUS_OPEN = 'Open';
    const STATUS_CLOSED = 'Closed';
    const STATUS_FOLLOW_UP = 'Follow-up';

    protected $fillable = [
        'case_no', 'title', 'chief_complaint', 'history_of_present_illness',
        'examination', 'examination_teeth', // SOAP: O - Objective
        'related_teeth', 'related_images', 'diagnosis_code',
        'auxiliary_examination', 'diagnosis', 'treatment', 'treatment_services', // SOAP: A & P
        'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup', 'visit_type',
        'signature', 'locked_at', 'modified_at', 'modified_by', 'modification_reason',
        'status', 'is_draft', 'case_date', 'closed_date', 'closing_notes',
        'patient_id', 'doctor_id', '_who_added'
    ];

    protected $casts = [
        'case_date' => 'date',
        'closed_date' => 'date',
        'next_visit_date' => 'date',
        'locked_at' => 'datetime',
        'modified_at' => 'datetime',
        'related_teeth' => 'array',
        'related_images' => 'array',
        'examination_teeth' => 'array',
        'treatment_services' => 'array',
        'auto_create_followup' => 'boolean',
        'is_draft' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function modifiedBy()
    {
        return $this->belongsTo('App\User', 'modified_by');
    }

    public function diagnoses()
    {
        return $this->hasMany('App\Diagnosis', 'medical_case_id');
    }

    public function progressNotes()
    {
        return $this->hasMany('App\ProgressNote', 'medical_case_id');
    }

    public function treatmentPlans()
    {
        return $this->hasMany('App\TreatmentPlan', 'medical_case_id');
    }

    public function appointments()
    {
        return $this->hasMany('App\Appointment', 'medical_case_id');
    }

    public function prescriptions()
    {
        return $this->hasMany('App\Prescription', 'medical_case_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'medical_case_id');
    }

    public function dentalCharts()
    {
        return $this->hasMany('App\DentalChart', 'medical_case_id');
    }

    public function treatmentMaterials()
    {
        return $this->hasMany('App\TreatmentMaterial', 'medical_case_id');
    }

    public function images()
    {
        return $this->hasMany('App\PatientImage', 'medical_case_id');
    }

    public function followups()
    {
        return $this->hasMany('App\PatientFollowup', 'medical_case_id');
    }

    /**
     * Check if case is locked
     */
    public function getIsLockedAttribute()
    {
        return !is_null($this->locked_at);
    }

    /**
     * Check if case can be modified without approval
     */
    public function canModifyWithoutApproval()
    {
        if (!$this->locked_at) {
            return true;
        }
        // Within 24 hours of locking
        return $this->locked_at->diffInHours(now()) < 24;
    }

    /**
     * Lock the case
     */
    public function lock()
    {
        $this->locked_at = now();
        $this->save();
        return $this;
    }

    /**
     * Record modification
     */
    public function recordModification($reason = null)
    {
        $this->modified_at = now();
        $this->modified_by = auth()->id();
        $this->modification_reason = $reason;
        $this->save();
        return $this;
    }

    /**
     * Scope for open cases
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for closed cases
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for initial visits
     */
    public function scopeInitialVisit($query)
    {
        return $query->where('visit_type', 'initial');
    }

    /**
     * Scope for revisits
     */
    public function scopeRevisit($query)
    {
        return $query->where('visit_type', 'revisit');
    }

    public static function CaseNumber()
    {
        $latest = self::latest()->first();
        if (!$latest) {
            return 'MC' . date('Y') . '0001';
        } else {
            return 'MC' . date('Y') . sprintf('%04d', $latest->id + 1);
        }
    }
}
