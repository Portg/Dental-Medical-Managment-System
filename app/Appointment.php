<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    const STATUS_WAITING = 'Waiting';
    const STATUS_TREATMENT_COMPLETE = 'Treatment Complete';
    const STATUS_TREATMENT_INCOMPLETE = 'Treatment Incomplete';
    const STATUS_RESCHEDULED = 'Rescheduled';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_REJECTED = 'Rejected';

    const VISIT_WALK_IN = 'walk_in';
    const VISIT_APPOINTMENT = 'appointment';
    const VISIT_SINGLE_TREATMENT = 'Single Treatment';
    const VISIT_REVIEW_TREATMENT = 'Review Treatment';

    protected $fillable = [
        'appointment_no', 'start_date', 'end_date', 'start_time',
        'duration_minutes', 'appointment_type', 'source',
        'notes', 'visit_information', 'status',
        'cancelled_reason', 'cancelled_by', 'no_show_count',
        'reminder_sent', 'reminder_sent_at', 'confirmed_by_patient', 'confirmed_at',
        'doctor_id', 'patient_id', 'branch_id', 'chair_id', 'service_id',
        'medical_case_id', 'sort_by', '_who_added'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sort_by' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'confirmed_by_patient' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Branch', 'branch_id');
    }

    public function chair()
    {
        return $this->belongsTo('App\Chair', 'chair_id');
    }

    public function service()
    {
        return $this->belongsTo('App\MedicalService', 'service_id');
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo('App\User', 'cancelled_by');
    }

    public function progressNotes()
    {
        return $this->hasMany('App\ProgressNote', 'appointment_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany('App\VitalSign', 'appointment_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'appointment_id');
    }

    public function treatmentMaterials()
    {
        return $this->hasMany('App\TreatmentMaterial', 'appointment_id');
    }

    public function stockOuts()
    {
        return $this->hasMany('App\StockOut', 'appointment_id');
    }

    /**
     * Scope for first visits
     */
    public function scopeFirstVisit($query)
    {
        return $query->where('appointment_type', 'first_visit');
    }

    /**
     * Scope for revisits
     */
    public function scopeRevisit($query)
    {
        return $query->where('appointment_type', 'revisit');
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_date', today());
    }

    /**
     * Scope for confirmed appointments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('confirmed_by_patient', true);
    }

    /**
     * Scope for no-shows
     */
    public function scopeNoShow($query)
    {
        return $query->where('status', self::STATUS_NO_SHOW);
    }

    /**
     * Check if reminder needs to be sent
     */
    public function needsReminder()
    {
        return !$this->reminder_sent
            && $this->status === self::STATUS_SCHEDULED
            && $this->start_date->isAfter(now());
    }

    /**
     * Mark as no-show and increment counter
     */
    public function markAsNoShow()
    {
        $this->status = self::STATUS_NO_SHOW;
        $this->no_show_count = ($this->no_show_count ?? 0) + 1;
        $this->save();

        // Also update patient's cumulative no-show count if needed
        return $this;
    }

    public static function AppointmentNo()
    {
        $latest = self::latest()->first();
        if (!$latest) {
            return date('Y') . "" . '0001';
        } else if ($latest->deleted_at != "null") {
            return time() + $latest->id + 1;
        } else {
            return date('Y') . "" . sprintf('%04d', $latest->id + 1);
        }
    }
}
