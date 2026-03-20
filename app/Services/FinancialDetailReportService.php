<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FinancialDetailReportService
{
    /**
     * AG-043: 收入来源 invoice_payments，支出 expense_payments，退款 refunds。
     */

    public function getPayments(?string $start, ?string $end, ?string $paymentType = null)
    {
        $q = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'invoice_payments._who_added')
            ->select(
                'invoice_payments.id',
                DB::raw('DATE_FORMAT(invoice_payments.created_at, "%Y-%m-%d") as payment_date'),
                'invoices.invoice_no',
                'patients.surname',
                'patients.othername',
                'invoice_payments.amount',
                DB::raw('COALESCE(invoice_payments.payment_method, "未知") as payment_type'),
                DB::raw('CONCAT(cashiers.surname, " ", cashiers.othername) as cashier_name')
            );

        if ($start) {
            $q->whereDate('invoice_payments.created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('invoice_payments.created_at', '<=', $end);
        }
        if ($paymentType) {
            $q->where('invoice_payments.payment_method', $paymentType);
        }

        return $q->orderByDesc('invoice_payments.created_at');
    }

    public function getRefunds(?string $start, ?string $end)
    {
        $q = DB::table('refunds')
            ->whereNull('refunds.deleted_at')
            ->leftJoin('invoices', 'invoices.id', '=', 'refunds.invoice_id')
            ->leftJoin('patients', 'patients.id', '=', 'refunds.patient_id')
            ->leftJoin('users as operators', 'operators.id', '=', 'refunds._who_added')
            ->select(
                'refunds.id',
                DB::raw('DATE_FORMAT(refunds.refund_date, "%Y-%m-%d") as refund_date'),
                'invoices.invoice_no',
                'patients.surname',
                'patients.othername',
                'refunds.refund_amount as amount',
                DB::raw('COALESCE(refunds.refund_reason, "") as reason'),
                DB::raw('CONCAT(operators.surname, " ", operators.othername) as operator_name')
            );

        if ($start) {
            $q->whereDate('refunds.created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('refunds.created_at', '<=', $end);
        }

        return $q->orderByDesc('refunds.created_at');
    }

    public function getExpenses(?string $start, ?string $end)
    {
        $q = DB::table('expense_payments')
            ->whereNull('expense_payments.deleted_at')
            ->leftJoin('expenses', 'expenses.id', '=', 'expense_payments.expense_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'expenses.supplier_id')
            ->leftJoin('users as operators', 'operators.id', '=', 'expense_payments._who_added')
            ->select(
                'expense_payments.id',
                DB::raw('DATE_FORMAT(expense_payments.created_at, "%Y-%m-%d") as payment_date'),
                DB::raw('COALESCE(expenses.purchase_no, "") as description'),
                DB::raw('COALESCE(suppliers.name, "未知供应商") as supplier_name'),
                'expense_payments.amount',
                DB::raw('CONCAT(operators.surname, " ", operators.othername) as operator_name')
            );

        if ($start) {
            $q->whereDate('expense_payments.created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('expense_payments.created_at', '<=', $end);
        }

        return $q->orderByDesc('expense_payments.created_at');
    }

    /** 员工收费明细 — 按收款人过滤的收款流水 */
    public function getEmployeeBilling(?string $start, ?string $end, ?int $cashierId = null)
    {
        $q = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'invoice_payments._who_added')
            ->select(
                'invoice_payments.id',
                DB::raw('DATE_FORMAT(invoice_payments.created_at, "%Y-%m-%d") as payment_date'),
                'invoices.invoice_no',
                'patients.surname',
                'patients.othername',
                DB::raw('COALESCE(invoice_payments.payment_method, "未知") as payment_type'),
                'invoice_payments.amount',
                DB::raw('CONCAT(cashiers.surname, " ", cashiers.othername) as cashier_name')
            );

        if ($start) {
            $q->whereDate('invoice_payments.created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('invoice_payments.created_at', '<=', $end);
        }
        if ($cashierId) {
            $q->where('invoice_payments._who_added', $cashierId);
        }

        return $q->orderByDesc('invoice_payments.created_at');
    }

    /** 获取收款员列表（用于下拉筛选） */
    public function getCashiers(): array
    {
        return DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->join('users', 'users.id', '=', 'invoice_payments._who_added')
            ->select('users.id', DB::raw('CONCAT(users.surname, " ", users.othername) as name'))
            ->groupBy('users.id', 'users.surname', 'users.othername')
            ->orderBy('users.surname')
            ->pluck('name', 'id')
            ->all();
    }

    public function getPaymentTypes(): array
    {
        return \App\DictItem::listByType('payment_method')
            ->pluck('name', 'code')
            ->all();
    }
}
