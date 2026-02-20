<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProceduresReportService
{
    /**
     * Get procedures income data filtered by date range and optional search.
     */
    public function getProceduresIncome(?string $startDate, ?string $endDate, ?string $search = null): Collection
    {
        $query = DB::table('invoice_items')
            ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->whereNull('invoice_items.deleted_at')
            ->select('medical_services.name', DB::raw('sum(invoice_items.amount*invoice_items.qty) as procedure_income'))
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
            ->select('medical_services.name', DB::raw('sum(invoice_items.amount*invoice_items.qty) as procedure_income'))
            ->groupBy('invoice_items.medical_service_id')
            ->orderBy('procedure_income', 'DESC')
            ->get();
    }
}
