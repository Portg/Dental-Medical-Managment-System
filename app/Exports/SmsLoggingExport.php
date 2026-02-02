<?php

namespace App\Exports;

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
        $rows = [];
        foreach ($this->data as $row) {
            $rows[] = [
                $row->created_at,
                $row->phone_number,
                $row->message,
                $row->cost,
                $row->status,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Created At',
            'Phone Number',
            'Message',
            'Cost',
            'Status',
        ];
    }
}
