<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'color',
        'discount_rate',
        'min_consumption',
        'points_rate',
        'benefits',
        'sort_order',
        'is_default',
        'is_active',
        'opening_fee',
        'min_initial_deposit',
        'deposit_bonus_rules',
        'referral_points',
        'payment_method_points_rates',
        '_who_added',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'deposit_bonus_rules' => 'array',
        'payment_method_points_rates' => 'array',
    ];

    /**
     * Get the members with this level.
     */
    public function members()
    {
        return $this->hasMany(Patient::class, 'member_level_id');
    }

    /**
     * Scope for active levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Calculate deposit bonus based on rules.
     * Returns the bonus amount for the given deposit amount.
     */
    public function calculateBonus(float $amount): float
    {
        $rules = $this->deposit_bonus_rules ?? [];
        if (empty($rules)) {
            return 0;
        }

        // Sort by min_amount descending to match highest tier first
        usort($rules, fn($a, $b) => ($b['min_amount'] ?? 0) <=> ($a['min_amount'] ?? 0));

        foreach ($rules as $rule) {
            if ($amount >= ($rule['min_amount'] ?? 0)) {
                return (float) ($rule['bonus'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Get points rate for a specific payment method.
     * Falls back to the level's default points_rate.
     */
    public function getPointsRateForMethod(string $method): float
    {
        $rates = $this->payment_method_points_rates;

        if ($rates && isset($rates[$method])) {
            return (float) $rates[$method];
        }

        return (float) $this->points_rate;
    }
}
