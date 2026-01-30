<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rule_name',
        'commission_mode',
        'target_service_type',
        'medical_service_id',
        'base_commission_rate',
        'tier1_threshold',
        'tier1_rate',
        'tier2_threshold',
        'tier2_rate',
        'tier3_threshold',
        'tier3_rate',
        'bonus_amount',
        'is_active',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_commission_rate' => 'decimal:2',
        'tier1_threshold' => 'decimal:2',
        'tier1_rate' => 'decimal:2',
        'tier2_threshold' => 'decimal:2',
        'tier2_rate' => 'decimal:2',
        'tier3_threshold' => 'decimal:2',
        'tier3_rate' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
    ];

    public function medicalService()
    {
        return $this->belongsTo(MedicalService::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateCommission($revenue)
    {
        switch ($this->commission_mode) {
            case 'fixed_percentage':
                return $revenue * ($this->base_commission_rate / 100);

            case 'tiered':
                return $this->calculateTieredCommission($revenue);

            case 'fixed_amount':
                return $this->bonus_amount;

            case 'mixed':
                $baseCommission = $revenue * ($this->base_commission_rate / 100);
                $tieredBonus = $this->calculateTieredCommission($revenue);
                return $baseCommission + $tieredBonus;

            default:
                return 0;
        }
    }

    protected function calculateTieredCommission($revenue)
    {
        $commission = 0;

        if ($this->tier3_threshold && $revenue > $this->tier3_threshold) {
            $commission += ($revenue - $this->tier3_threshold) * ($this->tier3_rate / 100);
            $revenue = $this->tier3_threshold;
        }

        if ($this->tier2_threshold && $revenue > $this->tier2_threshold) {
            $commission += ($revenue - $this->tier2_threshold) * ($this->tier2_rate / 100);
            $revenue = $this->tier2_threshold;
        }

        if ($this->tier1_threshold && $revenue > $this->tier1_threshold) {
            $commission += ($revenue - $this->tier1_threshold) * ($this->tier1_rate / 100);
        }

        return $commission;
    }
}
