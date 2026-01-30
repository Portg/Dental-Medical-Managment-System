<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientAnalytics extends Model
{
    protected $table = 'patient_analytics';

    protected $fillable = [
        'patient_id',
        'source_channel',
        'first_visit_date',
        'last_visit_date',
        'visit_count',
        'days_since_last_visit',
        'is_repeat_patient',
        'total_spent',
        'avg_transaction_value',
        'repeat_rate',
    ];

    protected $casts = [
        'first_visit_date' => 'date',
        'last_visit_date' => 'date',
        'is_repeat_patient' => 'boolean',
        'total_spent' => 'decimal:2',
        'avg_transaction_value' => 'decimal:2',
        'repeat_rate' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeRepeatPatients($query)
    {
        return $query->where('is_repeat_patient', true);
    }

    public function scopeNewPatients($query)
    {
        return $query->where('is_repeat_patient', false);
    }

    public function scopeInactive($query, $days = 180)
    {
        return $query->where('days_since_last_visit', '>', $days);
    }

    public static function refreshForPatient($patientId)
    {
        $patient = Patient::with('invoices', 'appointments')->find($patientId);
        if (!$patient) return null;

        $firstVisit = $patient->appointments()->orderBy('appointment_date')->first();
        $lastVisit = $patient->appointments()
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'desc')
            ->first();

        $visitCount = $patient->appointments()->where('status', 'completed')->count();
        $totalSpent = $patient->invoices()->sum('total_amount');

        $analytics = static::updateOrCreate(
            ['patient_id' => $patientId],
            [
                'source_channel' => $patient->source_channel ?? null,
                'first_visit_date' => $firstVisit?->appointment_date,
                'last_visit_date' => $lastVisit?->appointment_date,
                'visit_count' => $visitCount,
                'days_since_last_visit' => $lastVisit ? now()->diffInDays($lastVisit->appointment_date) : 0,
                'is_repeat_patient' => $visitCount > 1,
                'total_spent' => $totalSpent,
                'avg_transaction_value' => $visitCount > 0 ? $totalSpent / $visitCount : 0,
            ]
        );

        return $analytics;
    }
}
