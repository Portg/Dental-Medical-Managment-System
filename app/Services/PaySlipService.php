<?php

namespace App\Services;

use App\EmployeeContract;
use App\InvoiceItem;
use App\PaySlip;
use App\SalaryAdvance;
use App\SalaryAllowance;
use App\SalaryDeduction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaySlipService
{
    /**
     * Get pay slips for DataTables listing.
     */
    public function getPaySlipList(): Collection
    {
        return DB::table('pay_slips')
            ->leftJoin('users', 'users.id', 'pay_slips.employee_id')
            ->leftJoin('employee_contracts', 'employee_contracts.id', 'pay_slips.employee_contract_id')
            ->whereNull('pay_slips.deleted_at')
            ->select(
                'pay_slips.*',
                'employee_contracts.payroll_type',
                'employee_contracts.gross_salary',
                'employee_contracts.commission_percentage',
                'users.surname',
                'users.othername'
            )
            ->orderBy('pay_slips.id', 'desc')
            ->get();
    }

    /**
     * Calculate the wage for a pay slip row.
     */
    public function calculateWage(object $row): float
    {
        if ($row->payroll_type == 'Salary') {
            return (float) $row->gross_salary;
        }

        return $this->fetchDoctorCommission($row->employee_id, $row->payslip_month, $row->commission_percentage);
    }

    /**
     * Get total salary advances for a pay slip row.
     */
    public function employeeAdvances(object $row): float
    {
        return (float) SalaryAdvance::where([
            'advance_month' => $row->payslip_month,
            'employee_id' => $row->employee_id,
        ])->sum('advance_amount');
    }

    /**
     * Get total allowances for a pay slip.
     */
    public function employeeAllowances(object $row): float
    {
        return (float) SalaryAllowance::where('pay_slip_id', $row->id)->sum('allowance_amount');
    }

    /**
     * Get total deductions for a pay slip.
     */
    public function employeeDeductions(object $row): float
    {
        return (float) SalaryDeduction::where('pay_slip_id', $row->id)->sum('deduction_amount');
    }

    /**
     * Create a pay slip with allowances and deductions.
     *
     * @return array{status: bool, message: string}
     */
    public function createPaySlip(int $employeeId, string $payslipMonth, array $allowances, array $deductions): array
    {
        $employee = EmployeeContract::where(['employee_id' => $employeeId, 'status' => 'Active'])->first();
        if ($employee == null) {
            return ['status' => false, 'message' => __('payslips.employee_no_contract')];
        }

        $payslip = PaySlip::create([
            'payslip_month' => $payslipMonth,
            'employee_id' => $employeeId,
            'employee_contract_id' => $employee->id,
            '_who_added' => Auth::User()->id,
        ]);

        if (!$payslip) {
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }

        foreach ($allowances as $value) {
            if (!empty($value['allowance_amount'])) {
                SalaryAllowance::create([
                    'allowance' => $value['allowance'],
                    'allowance_amount' => $value['allowance_amount'],
                    'pay_slip_id' => $payslip->id,
                    '_who_added' => Auth::User()->id,
                ]);
            }
        }

        foreach ($deductions as $value) {
            if (!empty($value['deduction_amount'])) {
                SalaryDeduction::create([
                    'deduction' => $value['deduction'],
                    'deduction_amount' => $value['deduction_amount'],
                    'pay_slip_id' => $payslip->id,
                    '_who_added' => Auth::User()->id,
                ]);
            }
        }

        return ['status' => true, 'message' => __('payslips.payslip_generated_successfully')];
    }

    /**
     * Get pay slip data for the show page.
     */
    public function getPaySlipDetail(int $paySlipId): ?object
    {
        return DB::table('pay_slips')
            ->join('users', 'users.id', 'pay_slips.employee_id')
            ->where('pay_slips.id', $paySlipId)
            ->select('pay_slips.*', 'users.surname', 'users.othername')
            ->first();
    }

    /**
     * Delete a pay slip.
     */
    public function deletePaySlip(int $id): bool
    {
        return (bool) PaySlip::where('id', $id)->delete();
    }

    /**
     * Calculate doctor commission from invoices for a given month.
     */
    private function fetchDoctorCommission(int $doctorId, string $month, float $commissionPercentage): float
    {
        $invoices = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->whereNull('invoices.deleted_at')
            ->where('appointments.doctor_id', $doctorId)
            ->whereBetween(DB::raw('DATE_FORMAT(invoices.updated_at, \'%Y-%m\')'), [$month, $month])
            ->select('invoices.id')
            ->get();

        $totalAmount = 0;
        foreach ($invoices as $item) {
            $sumItemsAmount = InvoiceItem::where('invoice_id', $item->id)->sum(DB::raw('price*qty'));
            $totalAmount += $sumItemsAmount;
        }

        return ($commissionPercentage / 100) * $totalAmount;
    }
}
