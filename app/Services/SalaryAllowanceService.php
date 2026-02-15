<?php

namespace App\Services;

use App\SalaryAllowance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryAllowanceService
{
    /**
     * Get allowances for a pay slip.
     */
    public function getAllowancesForPaySlip(int $paySlipId): Collection
    {
        return DB::table('salary_allowances')
            ->join('users', 'users.id', 'salary_allowances._who_added')
            ->whereNull('salary_allowances.deleted_at')
            ->where('salary_allowances.pay_slip_id', '=', $paySlipId)
            ->select('salary_allowances.*', 'users.othername as added_by')
            ->orderBy('salary_allowances.updated_at', 'desc')
            ->get();
    }

    /**
     * Get a single allowance for editing.
     */
    public function getAllowanceForEdit(int $id): ?SalaryAllowance
    {
        return SalaryAllowance::where('id', $id)->first();
    }

    /**
     * Create a new salary allowance.
     */
    public function createAllowance(array $data, int $userId): ?SalaryAllowance
    {
        return SalaryAllowance::create([
            'allowance' => $data['allowance'],
            'allowance_amount' => $data['amount'],
            'pay_slip_id' => $data['pay_slip_id'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Update an existing salary allowance.
     */
    public function updateAllowance(int $id, array $data, int $userId): bool
    {
        return (bool) SalaryAllowance::where('id', $id)->update([
            'allowance' => $data['allowance'],
            'allowance_amount' => $data['amount'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Delete a salary allowance.
     */
    public function deleteAllowance(int $id): bool
    {
        return (bool) SalaryAllowance::where('id', $id)->delete();
    }
}
