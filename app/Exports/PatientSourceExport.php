<?php

namespace App\Exports;

use App\Exports\Sheets\SimpleTableSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 患者来源分析报表导出。
 *
 * 两个工作表：来源分析（含转化率）/ 月度新增趋势。
 * 本报表只聚合到「来源」维度，不含任何患者个人信息，
 * 因此无需 AG-045 的脱敏处理。
 */
class PatientSourceExport implements WithMultipleSheets
{
    private array $data;
    private string $startDate;
    private string $endDate;

    public function __construct(array $data, string $startDate, string $endDate)
    {
        $this->data      = $data;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function sheets(): array
    {
        return [
            $this->sourceAnalysisSheet(),
            $this->monthlyTrendSheet(),
        ];
    }

    private function sourceAnalysisSheet(): SimpleTableSheet
    {
        $rows = [];
        $totalPatients  = 0;
        $totalConverted = 0;

        foreach ($this->data['sourceAnalysis'] ?? [] as $item) {
            $count     = (int) data_get($item, 'patient_count', 0);
            $converted = (int) data_get($item, 'converted_count', 0);
            $totalPatients  += $count;
            $totalConverted += $converted;

            $rows[] = [
                data_get($item, 'name', ''),
                $count,
                data_get($item, 'percentage', 0) . '%',
                $converted,
                data_get($item, 'conversion_rate', 0) . '%',
            ];
        }

        // 合计行：转化率按总量重算，不是各行百分比的平均
        $rows[] = [
            __('common.total'),
            $totalPatients,
            '100%',
            $totalConverted,
            ($totalPatients > 0 ? round($totalConverted / $totalPatients * 100, 1) : 0) . '%',
        ];

        return new SimpleTableSheet(
            __('report.source_analysis'),
            [
                __('report.source'),
                __('report.patient_count'),
                __('report.percentage'),
                __('report.converted_count'),
                __('report.conversion_rate'),
            ],
            $rows,
            true
        );
    }

    private function monthlyTrendSheet(): SimpleTableSheet
    {
        // 查询是按 month + source_id 分组的明细，这里按月汇总成一行，
        // 便于在 Excel 里直接看趋势。
        $byMonth = [];
        foreach ($this->data['monthlyTrend'] ?? [] as $row) {
            $month = data_get($row, 'month', '');
            if ($month === '') {
                continue;
            }
            $byMonth[$month] = ($byMonth[$month] ?? 0) + (int) data_get($row, 'count', 0);
        }
        ksort($byMonth);

        $rows = [];
        foreach ($byMonth as $month => $count) {
            $rows[] = [$month, $count];
        }

        return new SimpleTableSheet(
            __('report.monthly_trend'),
            [__('report.month'), __('report.new_patients')],
            $rows
        );
    }
}
