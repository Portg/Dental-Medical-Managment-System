<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'prescription_no', 'drug', 'qty', 'directions',
        'status', 'prescription_date', 'expiry_date',
        'refills_allowed', 'refills_used', 'doctor_signature', 'notes',
        'appointment_id', 'medical_case_id', 'patient_id', 'doctor_id', '_who_added'
    ];

    protected $casts = [
        'prescription_date' => 'date',
        'expiry_date' => 'date',
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

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function items()
    {
        return $this->hasMany('App\PrescriptionItem', 'prescription_id');
    }

    /**
     * Scope for pending prescriptions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed prescriptions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for active prescriptions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'filled'])
                     ->where(function ($q) {
                         $q->whereNull('expiry_date')
                           ->orWhere('expiry_date', '>=', now());
                     });
    }

    /**
     * Check if prescription is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->lt(now());
    }

    /**
     * Check if refills are available
     */
    public function getCanRefillAttribute()
    {
        return $this->refills_used < $this->refills_allowed && !$this->is_expired;
    }

    /**
     * Use a refill
     */
    public function useRefill()
    {
        if ($this->can_refill) {
            $this->refills_used++;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Generate prescription number
     */
    public static function generatePrescriptionNo()
    {
        $prefix = 'RX' . date('Ymd');
        $latest = self::where('prescription_no', 'like', $prefix . '%')
            ->orderBy('prescription_no', 'desc')
            ->first();

        if (!$latest) {
            return $prefix . '0001';
        } else {
            $lastNumber = intval(substr($latest->prescription_no, -4));
            return $prefix . sprintf('%04d', $lastNumber + 1);
        }
    }
}
