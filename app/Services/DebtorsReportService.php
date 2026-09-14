<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DebtorsReportService
{
    /**
     * Get debtors data for the DataTables listing.
     */
    public function getDebtorsData(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->buildDebtorsArray(false, $startDate, $endDate);
    }

    /**
     * Get debtors data for export (includes insurance company).
     */
    public function getDebtorsExportData(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->buildDebtorsArray(true, $startDate, $endDate);
    }

    /**
     * Build the debtors array with outstanding balances using a single query.
     *
     * @param bool $includeInsurance Whether to include insurance company info (for export).
     * @param string|null $startDate Optional start date filter (Y-m-d).
     * @param string|null $endDate   Optional end date filter (Y-m-d).
     */
    private function buildDebtorsArray(bool $includeInsurance, ?string $startDate = null, ?string $endDate = null): array
    {
        // 金额一律取 invoices 上的权威列，不再从 invoice_items 汇总。
        //
        // 原来写的是 SUM(invoice_items.amount * qty)，而 invoice_items.amount 是 2019 年的
        // 遗留列，两条开单路径都从未写它（见 InvoiceItemService::getItemsByAppointment
        // 的注释），恒为 0。于是 outstanding_balance = 0 - 已收 = 负数，再被末尾那句
        // havingRaw('outstanding_balance > 0') 整条滤掉 —— 划价面板开出来的单一张都
        // 进不了欠款报表，报表显示「没有欠款」。而划价面板现在是工作台与患者页
        // 唯一的开单入口。
        //
        // 也不是把 amount 换成 COALESCE(actual_paid, ...) 就对：整单折扣记在 invoice 上
        // （order_discount_amount），明细行里没有，从行汇总会把欠款算多。
        // invoices.total_amount / paid_amount / outstanding_amount 由 Invoice::saving()
        // 统一维护，是这件事唯一的真相。
        $selectColumns = [
            'invoices.id as invoice_id',
            'invoices.invoice_no',
            DB::raw('DATE_FORMAT(invoices.created_at, "%Y-%m-%d") as invoice_date'),
            'patients.surname',
            'patients.othername',
            'patients.phone_no',
            'invoices.total_amount as invoice_amount',
            'invoices.paid_amount as amount_paid',
            'invoices.outstanding_amount as outstanding_balance',
        ];

        if ($includeInsurance) {
            $selectColumns[] = 'insurance_companies.name as insurance_company';
        }

        $query = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
            // 患者走 COALESCE(invoices.patient_id, appointments.patient_id)：患者页开的单
            // 没有 appointment_id，只按预约找患者的话，这些单的姓名和电话是空的。
            // 与 InvoiceService::searchInvoices 用的是同一个写法。
            ->leftJoin('patients', 'patients.id', DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'))
            ->whereNull('invoices.deleted_at');

        if ($includeInsurance) {
            $query->leftJoin('insurance_companies', 'insurance_companies.id', '=', 'patients.insurance_company_id');
        }

        if ($startDate) {
            $query->whereDate('invoices.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('invoices.created_at', '<=', $endDate);
        }

        // 不再需要 GROUP BY：金额来自 invoices 自己的列，一张单就是一行，
        // 原来那组 groupBy 只是为了把 join invoice_items 炸开的行收回去。
        $rows = $query->select($selectColumns)
            ->where('invoices.outstanding_amount', '>', 0)
            ->orderByDesc('invoices.outstanding_amount')
            ->get();

        $output = [];
        foreach ($rows as $row) {
            $item = [
                'invoice_date'        => $row->invoice_date,
                'invoice_no'          => $row->invoice_no,
                'surname'             => $row->surname,
                'othername'           => $row->othername,
                'phone_no'            => $row->phone_no,
                'invoice_amount'      => $row->invoice_amount,
                'amount_paid'         => $row->amount_paid,
                'outstanding_balance' => $row->outstanding_balance,
            ];

            if ($includeInsurance) {
                $item['insurance_company'] = $row->insurance_company;
            }

            $output[] = $item;
        }

        return $output;
    }
}
