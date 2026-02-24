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
        $statusMap = [
            'Pending' => __('appointment.pending'),
            'Confirmed' => __('appointment.confirmed'),
            'Scheduled' => __('appointment.scheduled'),
            'Arrived' => __('appointment.arrived'),
            'In Progress' => __('appointment.in_progress'),
            'Completed' => __('appointment.completed'),
            'Cancelled' => __('appointment.cancelled'),
            'No Show' => __('appointment.no_show'),
            'Rescheduled' => __('appointment.rescheduled'),
            'Waiting' => __('appointment.waiting'),
        ];

        $rows = [];
        foreach ($this->data as $row) {
            $fullName = trim(($row->surname ?? '') . ($row->othername ?? ''));
            $rows[] = [
                $fullName,
                $row->start_date,
                $row->start_time,
                $row->visit_information,
                $statusMap[$row->status] ?? $row->status,
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
