<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashSummaryExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    private array $data;
    private string $tab;
    private string $startDate;
    private string $endDate;
    private int $totalRow = 0;

    public function __construct(array $data, string $tab, string $startDate, string $endDate)
    {
        $this->data      = $data;
        $this->tab       = $tab;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function title(): string
    {
        return __('report.cash_summary_report');
    }

    public function headings(): array
    {
        return [
            $this->data['label_col'] ?? '分组',
            __('report.bill_count'),
            __('report.total_amount'),
            __('report.percentage'),
        ];
    }

    public function array(): array
    {
        $rows   = $this->data['rows'] ?? collect();
        $total  = $rows->sum('total_amount');
        $output = [];

        foreach ($rows as $row) {
            $pct = $total > 0 ? round($row->total_amount / $total * 100, 1) : 0;
            $output[] = [
                $row->label,
                $row->bill_count,
                number_format($row->total_amount, 2),
                $pct . '%',
            ];
        }

        // Total row
        $output[] = [__('common.total'), $rows->sum('bill_count'), number_format($total, 2), '100%'];
        $this->totalRow = count($output) + 1;

        return $output;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRow) {
            $sheet->getStyle('A' . $this->totalRow . ':D' . $this->totalRow)->getFont()->setBold(true);
        }
        return [];
    }
}
