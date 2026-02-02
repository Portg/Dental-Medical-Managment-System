<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientFollowup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'followup_no',
        'followup_type',
        'scheduled_date',
        'completed_date',
        'status',
        'purpose',
        'notes',
        'outcome',
        'reminder_sent',
        'next_followup_date',
        'patient_id',
        'appointment_id',
        'medical_case_id',
        '_who_added',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'next_followup_date' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    /**
     * Generate unique followup number
     */
    public static function generateFollowupNo()
    {
        $year = date('Y');
        $lastFollowup = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastFollowup) {
            $lastNumber = intval(substr($lastFollowup->followup_no, -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'FU' . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the patient that owns the followup.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment associated with the followup.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the medical case associated with the followup.
     */
    public function medicalCase()
    {
        return $this->belongsTo(MedicalCase::class, 'medical_case_id');
    }

    /**
     * Get the user who added the followup.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Scope for pending followups.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope for overdue followups.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Pending')
            ->where('scheduled_date', '<', now()->toDateString());
    }

    /**
     * Scope for upcoming followups within days.
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('status', 'Pending')
            ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /**
     * Check if followup is overdue.
     */
    public function getIsOverdueAttribute()
    {
        return $this->status === 'Pending' && $this->scheduled_date < now()->toDateString();
    }
}
