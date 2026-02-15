<?php

namespace App\Services;

use App\EmployeeContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeContractService
{
    /**
     * Get all employee contracts for DataTables.
     */
    public function getContractList(): Collection
    {
        return DB::table('employee_contracts')
            ->leftJoin('users', 'users.id', 'employee_contracts.employee_id')
            ->leftJoin('users as loggedInUser', 'loggedInUser.id', 'employee_contracts._who_added')
            ->whereNull('employee_contracts.deleted_at')
            ->select(
                'employee_contracts.*',
                'users.surname',
                'users.othername',
                'loggedInUser.othername as loggedInName'
            )
            ->orderBy('employee_contracts.id', 'desc')
            ->get();
    }

    /**
     * Deactivate any existing contracts for the employee, then create a new one.
     */
    public function createContract(array $data, int $userId): ?EmployeeContract
    {
        $this->deactivateExistingContracts($data['employee']);

        return EmployeeContract::create([
            'employee_id' => $data['employee'],
            'contract_type' => $data['contract_type'],
            'start_date' => $data['start_date'],
            'contract_length' => $data['contract_length'],
            'contract_period' => $data['contract_period'],
            'payroll_type' => $data['payroll_type'],
            'gross_salary' => $data['gross_salary'] ?? null,
            'commission_percentage' => $data['commission_percentage'] ?? null,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Get contract data for editing.
     */
    public function getContractForEdit(int $id): ?object
    {
        return DB::table('employee_contracts')
            ->join('users', 'users.id', 'employee_contracts.employee_id')
            ->where('employee_contracts.id', $id)
            ->select('employee_contracts.*', 'users.surname', 'users.othername')
            ->first();
    }

    /**
     * Update an existing contract.
     */
    public function updateContract(int $id, array $data, int $userId): bool
    {
        return (bool) EmployeeContract::where('id', $id)->update([
            'employee_id' => $data['employee'],
            'contract_type' => $data['contract_type'],
            'start_date' => $data['start_date'],
            'contract_length' => $data['contract_length'],
            'contract_period' => $data['contract_period'],
            'payroll_type' => $data['payroll_type'],
            'gross_salary' => $data['gross_salary'] ?? null,
            'commission_percentage' => $data['commission_percentage'] ?? null,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Delete a contract (soft-delete).
     */
    public function deleteContract(int $id): bool
    {
        return (bool) EmployeeContract::where('id', $id)->delete();
    }

    /**
     * Calculate contract end date.
     */
    public function calculateContractEndDate(string $contractPeriod, string $startDate, int $contractLength): string
    {
        $days = $contractPeriod === 'Months' ? 30 : 365;
        $totalDays = $days * $contractLength;

        return date('Y-m-d', strtotime($startDate . ' + ' . $totalDays . ' days'));
    }

    /**
     * Deactivate existing contracts for an employee.
     */
    private function deactivateExistingContracts(int $employeeId): void
    {
        $count = EmployeeContract::where('employee_id', $employeeId)->count();
        if ($count > 0) {
            EmployeeContract::where('employee_id', $employeeId)->update(['status' => 'Expired']);
        }
    }
}
