<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = [
        'report_date',
        'branch_id',
        'total_revenue',
        'refund_amount',
        'net_revenue',
        'transaction_count',
        'no_show_count',
        'new_patient_count',
        'appointment_count',
        'revenue_by_category',
        'revenue_by_payment_method',
        'doctor_performance',
        '_who_added',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_revenue' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_revenue' => 'decimal:2',
        'revenue_by_category' => 'array',
        'revenue_by_payment_method' => 'array',
        'doctor_performance' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('report_date', $date);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }
}
