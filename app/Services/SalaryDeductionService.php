<?php

namespace App\Services;

use App\SalaryDeduction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryDeductionService
{
    /**
     * Get deductions for a pay slip.
     */
    public function getDeductionsForPaySlip(int $paySlipId): Collection
    {
        return DB::table('salary_deductions')
            ->join('users', 'users.id', 'salary_deductions._who_added')
            ->whereNull('salary_deductions.deleted_at')
            ->where('salary_deductions.pay_slip_id', '=', $paySlipId)
            ->select('salary_deductions.*', 'users.othername as added_by')
            ->orderBy('salary_deductions.updated_at', 'desc')
            ->get();
    }

    /**
     * Get a single deduction for editing.
     */
    public function getDeductionForEdit(int $id): ?SalaryDeduction
    {
        return SalaryDeduction::where('id', $id)->first();
    }

    /**
     * Create a new salary deduction.
     */
    public function createDeduction(array $data, int $userId): ?SalaryDeduction
    {
        return SalaryDeduction::create([
            'deduction' => $data['deduction'],
            'deduction_amount' => $data['amount'],
            'pay_slip_id' => $data['pay_slip_id'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Update an existing salary deduction.
     */
    public function updateDeduction(int $id, array $data, int $userId): bool
    {
        return (bool) SalaryDeduction::where('id', $id)->update([
            'deduction' => $data['deduction'],
            'deduction_amount' => $data['amount'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Delete a salary deduction.
     */
    public function deleteDeduction(int $id): bool
    {
        return (bool) SalaryDeduction::where('id', $id)->delete();
    }
}
