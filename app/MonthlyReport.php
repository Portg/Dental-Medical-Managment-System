<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyReport extends Model
{
    protected $fillable = [
        'report_year_month',
        'branch_id',
        'total_revenue',
        'previous_month_revenue',
        'same_month_last_year_revenue',
        'new_patient_count',
        'repeat_patient_count',
        'avg_transaction_value',
        'transaction_count',
        'no_show_rate',
        'top_services',
        'doctor_rankings',
        'data',
        '_who_added',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'previous_month_revenue' => 'decimal:2',
        'same_month_last_year_revenue' => 'decimal:2',
        'avg_transaction_value' => 'decimal:2',
        'no_show_rate' => 'decimal:2',
        'top_services' => 'array',
        'doctor_rankings' => 'array',
        'data' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeForMonth($query, $yearMonth)
    {
        return $query->where('report_year_month', $yearMonth);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function getMonthOverMonthGrowthAttribute()
    {
        if ($this->previous_month_revenue > 0) {
            return (($this->total_revenue - $this->previous_month_revenue) / $this->previous_month_revenue) * 100;
        }
        return 0;
    }

    public function getYearOverYearGrowthAttribute()
    {
        if ($this->same_month_last_year_revenue > 0) {
            return (($this->total_revenue - $this->same_month_last_year_revenue) / $this->same_month_last_year_revenue) * 100;
        }
        return 0;
    }
}
