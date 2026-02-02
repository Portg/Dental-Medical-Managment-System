<?php

namespace App\Exports;

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
            $rows[] = [
                $row->surname,
                $row->othername,
                $row->start_date,
                $row->start_time,
                $row->visit_information,
                $row->status,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Surname',
            'Other Name',
            __('appointment.appointment_date'),
            __('appointment.appointment_time'),
            __('appointment.visit_information'),
            __('appointment.appointment_status'),
        ];
    }
}
