<?php

namespace App\Services;

use App\Appointment;
use App\ExpensePayment;
use App\InsuranceCompany;
use App\InvoicePayment;
use App\Patient;
use App\User;
use Illuminate\Support\Carbon;

class ReceptionistDashboardService
{
    /**
     * Get all dashboard data for the receptionist.
     */
    public function getDashboardData(): array
    {
        return [
            'total_patients' => Patient::count(),
            'total_users' => User::count(),
            'total_insurance_company' => InsuranceCompany::count(),
            'today_appointments' => Appointment::where('updated_at', '>', Carbon::today())->count(),
            'today_cash_amount' => InvoicePayment::where('payment_method', 'Cash')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'today_Insurance_amount' => InvoicePayment::where('payment_method', 'Insurance')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'today_expense_amount' => ExpensePayment::whereDate('payment_date', date('Y-m-d'))->sum('amount'),
        ];
    }
}
