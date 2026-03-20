<?php

namespace App\Exports;

use App\DictItem;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AppointmentExport implements FromArray, WithHeadings, ShouldAutoSize
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
            $fullName = trim(($row->surname ?? '') . ($row->othername ?? ''));
            $rows[] = [
                $fullName,
                $row->start_date,
                $row->start_time,
                DictItem::nameByCode('appointment_visit_information', $row->visit_information) ?? $row->visit_information,
                DictItem::nameByCode('appointment_status', $row->status) ?? $row->status,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            __('patient.full_name'),
            __('appointment.appointment_date'),
            __('appointment.appointment_time'),
            __('appointment.visit_information'),
            __('appointment.appointment_status'),
        ];
    }
}
