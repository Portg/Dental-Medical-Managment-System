<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DentalChart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tooth', 'tooth_number', 'tooth_type', 'tooth_status',
        'position', 'color', 'kind', 'surface', 'notes',
        'appointment_id', 'medical_case_id', 'patient_id',
        'doctor_id', 'changed_at', 'changed_by', '_who_added'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->changed_at = now();
            $model->changed_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->changed_at = now();
            $model->changed_by = auth()->id();
        });
    }

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

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function changedBy()
    {
        return $this->belongsTo('App\User', 'changed_by');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function treatmentMaterials()
    {
        return $this->hasMany('App\TreatmentMaterial', 'dental_chart_id');
    }

    /**
     * Scope for permanent teeth
     */
    public function scopePermanent($query)
    {
        return $query->where('tooth_type', 'permanent');
    }

    /**
     * Scope for primary teeth
     */
    public function scopePrimary($query)
    {
        return $query->where('tooth_type', 'primary');
    }

    /**
     * Scope by tooth status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('tooth_status', $status);
    }

    /**
     * Scope for problem teeth (not normal)
     */
    public function scopeProblemTeeth($query)
    {
        return $query->where('tooth_status', '!=', 'normal');
    }

    /**
     * Get FDI notation for the tooth
     */
    public function getFdiNotationAttribute()
    {
        return $this->tooth_number ?? $this->tooth;
    }

    /**
     * Check if tooth status is critical (requires doctor confirmation)
     */
    public function isCriticalStatusChange($newStatus)
    {
        $criticalChanges = [
            'normal' => ['caries', 'rct', 'missing', 'extraction_planned'],
            'missing' => ['implant'],
        ];

        return isset($criticalChanges[$this->tooth_status])
            && in_array($newStatus, $criticalChanges[$this->tooth_status]);
    }
}
