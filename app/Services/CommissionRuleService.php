<?php

namespace App\Services;

use App\CommissionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionRuleService
{
    /**
     * Get all commission rules for DataTables listing.
     */
    public function getList(): Collection
    {
        return DB::table('commission_rules')
            ->leftJoin('medical_services', 'medical_services.id', 'commission_rules.medical_service_id')
            ->leftJoin('branches', 'branches.id', 'commission_rules.branch_id')
            ->leftJoin('users', 'users.id', 'commission_rules._who_added')
            ->whereNull('commission_rules.deleted_at')
            ->select([
                'commission_rules.*',
                'medical_services.name as service_name',
                'branches.name as branch_name',
                'users.surname as added_by'
            ])
            ->orderBy('commission_rules.id', 'desc')
            ->get();
    }

    /**
     * Get view data (services and branches for dropdowns).
     */
    public function getViewData(): array
    {
        $services = \App\MedicalService::all();
        $branches = \App\Branch::all();

        return compact('services', 'branches');
    }

    /**
     * Create a new commission rule.
     */
    public function create(array $input): ?CommissionRule
    {
        return CommissionRule::create([
            'rule_name' => $input['rule_name'],
            'commission_mode' => $input['commission_mode'],
            'target_service_type' => $input['target_service_type'] ?? null,
            'medical_service_id' => $input['medical_service_id'] ?: null,
            'base_commission_rate' => $input['base_commission_rate'] ?: 0,
            'tier1_threshold' => $input['tier1_threshold'] ?: null,
            'tier1_rate' => $input['tier1_rate'] ?: null,
            'tier2_threshold' => $input['tier2_threshold'] ?: null,
            'tier2_rate' => $input['tier2_rate'] ?: null,
            'tier3_threshold' => $input['tier3_threshold'] ?: null,
            'tier3_rate' => $input['tier3_rate'] ?: null,
            'bonus_amount' => $input['bonus_amount'] ?: 0,
            'is_active' => !empty($input['is_active']),
            'branch_id' => $input['branch_id'] ?: null,
            '_who_added' => Auth::user()->id,
        ]);
    }

    /**
     * Find a commission rule by ID.
     */
    public function find(int $id): ?CommissionRule
    {
        return CommissionRule::where('id', $id)->first();
    }

    /**
     * Update an existing commission rule.
     */
    public function update(int $id, array $input): bool
    {
        return (bool) CommissionRule::where('id', $id)->update([
            'rule_name' => $input['rule_name'],
            'commission_mode' => $input['commission_mode'],
            'target_service_type' => $input['target_service_type'] ?? null,
            'medical_service_id' => $input['medical_service_id'] ?: null,
            'base_commission_rate' => $input['base_commission_rate'] ?: 0,
            'tier1_threshold' => $input['tier1_threshold'] ?: null,
            'tier1_rate' => $input['tier1_rate'] ?: null,
            'tier2_threshold' => $input['tier2_threshold'] ?: null,
            'tier2_rate' => $input['tier2_rate'] ?: null,
            'tier3_threshold' => $input['tier3_threshold'] ?: null,
            'tier3_rate' => $input['tier3_rate'] ?: null,
            'bonus_amount' => $input['bonus_amount'] ?: 0,
            'is_active' => !empty($input['is_active']),
            'branch_id' => $input['branch_id'] ?: null,
        ]);
    }

    /**
     * Delete a commission rule (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) CommissionRule::where('id', $id)->delete();
    }

    /**
     * Calculate commission for a given service and revenue.
     */
    public function calculateCommission(?int $serviceId, ?string $serviceType, float $revenue): array
    {
        $rule = CommissionRule::where('medical_service_id', $serviceId)
            ->orWhere('target_service_type', $serviceType)
            ->active()
            ->first();

        if (!$rule) {
            return ['commission' => 0, 'message' => __('commission_rules.no_rule_found')];
        }

        $commission = $rule->calculateCommission($revenue);

        return [
            'commission' => round($commission, 2),
            'rule_name' => $rule->rule_name,
            'mode' => $rule->commission_mode,
        ];
    }
}
