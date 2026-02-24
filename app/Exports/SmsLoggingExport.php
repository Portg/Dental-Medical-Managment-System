<?php

namespace App\Exports;

use App\Services\DataMaskingService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SmsLoggingExport implements FromArray, WithHeadings, ShouldAutoSize
{
    private $data;

    public function __construct($queryData)
    {
        $this->data = $queryData;
    }

    public function array(): array
    {
        $mask = DataMaskingService::isExportMaskingEnabled();
        $statusMap = [
            'sent' => __('sms.sent'),
            'delivered' => __('sms.delivered'),
            'failed' => __('sms.failed'),
            'undelivered' => __('sms.undelivered'),
            'queued' => __('sms.queued'),
        ];

        $rows = [];
        foreach ($this->data as $row) {
            $rows[] = [
                $row->created_at,
                $mask ? DataMaskingService::maskPhone($row->phone_number) : $row->phone_number,
                $row->message,
                $row->cost,
                $statusMap[$row->status] ?? $row->status,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            __('sms.sent_date'),
            __('sms.phone_number'),
            __('sms.message'),
            __('sms.cost_per_sms'),
            __('sms.status'),
        ];
    }
}
