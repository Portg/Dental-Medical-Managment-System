<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DoctorPerformance extends Model
{
    protected $table = 'doctor_performance';

    protected $fillable = [
        'doctor_id',
        'period_start_date',
        'period_end_date',
        'total_revenue',
        'transaction_count',
        'new_patient_count',
        'avg_transaction_value',
        'total_commission',
        'commission_rate',
        'achievement_rate',
        'target_revenue',
        'revenue_by_service',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'total_revenue' => 'decimal:2',
        'avg_transaction_value' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'achievement_rate' => 'decimal:2',
        'target_revenue' => 'decimal:2',
        'revenue_by_service' => 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start_date', '>=', $startDate)
                     ->where('period_end_date', '<=', $endDate);
    }

    public function calculateAchievementRate()
    {
        if ($this->target_revenue > 0) {
            $this->achievement_rate = ($this->total_revenue / $this->target_revenue) * 100;
            $this->save();
        }
        return $this->achievement_rate;
    }
}
