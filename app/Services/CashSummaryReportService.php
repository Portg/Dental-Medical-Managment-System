<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CashSummaryReportService
{
    /**
     * 获取现金汇总数据，按维度（Tab）分组。
     *
     * AG-043: 收入来源 invoice_payments。
     */
    public function getData(string $tab, string $startDate, string $endDate): array
    {
        return match ($tab) {
            'payment_method'   => $this->byPaymentMethod($startDate, $endDate),
            'collector'        => $this->byCollector($startDate, $endDate),
            'date'             => $this->byDate($startDate, $endDate),
            'doctor'           => $this->byDoctor($startDate, $endDate),
            'service_category' => $this->byServiceCategory($startDate, $endDate),
            default            => $this->byPaymentMethod($startDate, $endDate),
        };
    }

    /** 按支付方式汇总 */
    private function byPaymentMethod(string $start, string $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->whereDate('invoice_payments.created_at', '>=', $start)
            ->whereDate('invoice_payments.created_at', '<=', $end)
            ->select(
                DB::raw('COALESCE(payment_method, "未知") as label'),
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        return ['rows' => $rows, 'label_col' => __('report.by_payment_method')];
    }

    /** 按收款人汇总 */
    private function byCollector(string $start, string $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->whereDate('invoice_payments.created_at', '>=', $start)
            ->whereDate('invoice_payments.created_at', '<=', $end)
            ->leftJoin('users', 'users.id', '=', 'invoice_payments._who_added')
            ->select(
                DB::raw('COALESCE(CONCAT(users.surname, " ", users.othername), "未知") as label'),
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(invoice_payments.amount) as total_amount')
            )
            ->groupBy('invoice_payments._who_added', 'users.surname', 'users.othername')
            ->orderByDesc('total_amount')
            ->get();

        return ['rows' => $rows, 'label_col' => __('report.by_collector')];
    }

    /** 按日期汇总 */
    private function byDate(string $start, string $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select(
                DB::raw('DATE(created_at) as label'),
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('label')
            ->get();

        return ['rows' => $rows, 'label_col' => __('report.by_date')];
    }

    /** 按收费大类汇总（通过 invoice_payment → invoice → invoice_item → medical_service） */
    private function byServiceCategory(string $start, string $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->whereDate('invoice_payments.created_at', '>=', $start)
            ->whereDate('invoice_payments.created_at', '<=', $end)
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->join('invoice_items', function ($join) {
                $join->on('invoice_items.invoice_id', '=', 'invoices.id')
                     ->whereNull('invoice_items.deleted_at');
            })
            ->leftJoin('medical_services', 'medical_services.id', '=', 'invoice_items.medical_service_id')
            ->select(
                DB::raw('COALESCE(medical_services.category, "未分类") as label'),
                DB::raw('COUNT(DISTINCT invoices.id) as bill_count'),
                DB::raw('SUM(invoice_items.amount) as total_amount')
            )
            ->groupBy('medical_services.category')
            ->orderByDesc('total_amount')
            ->get();

        return ['rows' => $rows, 'label_col' => __('report.by_service_category')];
    }

    /** 按医生汇总（通过 appointment → invoice → invoice_payment） */
    private function byDoctor(string $start, string $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereNull('invoice_payments.deleted_at')
            ->whereDate('invoice_payments.created_at', '>=', $start)
            ->whereDate('invoice_payments.created_at', '<=', $end)
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
            ->leftJoin('users as doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->select(
                DB::raw('COALESCE(CONCAT(doctors.surname, " ", doctors.othername), "未分配") as label'),
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(invoice_payments.amount) as total_amount')
            )
            ->groupBy('appointments.doctor_id', 'doctors.surname', 'doctors.othername')
            ->orderByDesc('total_amount')
            ->get();

        return ['rows' => $rows, 'label_col' => __('report.by_doctor')];
    }
}
