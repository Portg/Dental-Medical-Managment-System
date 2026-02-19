<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PatientExport implements FromArray, WithHeadings, ShouldAutoSize
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
                $row->gender,
                $row->dob ?? '',
                $row->phone_no ?? '',
                $row->alternative_no ?? '',
                $row->address ?? '',
                $row->profession ?? '',
                $row->next_of_kin ?? '',
                $row->has_insurance ? __('common.yes') : __('common.no'),
                $row->insurance_company ?? '',
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Surname',
            'Other Name',
            'Gender',
            'DOB',
            'Phone No',
            'Alternative No',
            'Address',
            'Profession',
            'Next of Kin',
            'Has Insurance',
            'Insurance Company',
        ];
    }
}
