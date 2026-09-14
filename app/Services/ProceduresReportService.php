<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProceduresReportService
{
    /**
     * 每个诊疗项目的收入。
     *
     * 金额取 COALESCE(actual_paid, discounted_price, price * qty)，**不再乘 qty**：
     * actual_paid 与 discounted_price 存的本来就是含数量的行小计
     * （见 InvoiceService::createBillingInvoice 里 $lineTotal = price × qty 的赋值），
     * 再乘一次数量会把多颗牙的项目按平方放大。
     *
     * 原来写的是 sum(invoice_items.amount * qty)，而 amount 是 2019 年的遗留列，
     * 两条开单路径都从未写它，恒为 0 —— 这张报表对划价面板开出来的单一律算成 0，
     * 而划价面板现在是唯一的开单入口。
     *
     * 用 COALESCE 而不是 NULLIF(x, 0)：actual_paid 真的是 0 是合法的（赠送项目），
     * 不该被当成缺值退回原价。与 InvoiceItemService::getItemsByAppointment 同一写法。
     */
    private const INCOME_EXPR =
        'sum(COALESCE(invoice_items.actual_paid, invoice_items.discounted_price, invoice_items.price * invoice_items.qty)) as procedure_income';

    /**
     * Get procedures income data filtered by date range and optional search.
     */
    public function getProceduresIncome(?string $startDate, ?string $endDate, ?string $search = null): Collection
    {
        $query = DB::table('invoice_items')
            ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->whereNull('invoice_items.deleted_at')
            ->select('medical_services.name', DB::raw(self::INCOME_EXPR))
            ->groupBy('invoice_items.medical_service_id')
            ->orderBy('procedure_income', 'DESC');

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'), [$startDate, $endDate]);
        }

        if ($search) {
            $query->where('medical_services.name', 'like', '%' . $search . '%');
        }

        return $query->get();
    }

    /**
     * Get procedures data for export from session dates.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        if (empty($from) || empty($to)) {
            return collect();
        }

        return DB::table('invoice_items')
            ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->whereNull('invoice_items.deleted_at')
            ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'), [$from, $to])
            ->select('medical_services.name', DB::raw(self::INCOME_EXPR))
            ->groupBy('invoice_items.medical_service_id')
            ->orderBy('procedure_income', 'DESC')
            ->get();
    }
}
