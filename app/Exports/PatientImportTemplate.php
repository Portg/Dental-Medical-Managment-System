<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PatientImportTemplate implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            __('patient.full_name'),
            __('patient.gender'),
            __('patient.phone_no'),
            __('patient.dob'),
            __('patient.alternative_phone_no'),
            __('patient.email_address'),
            __('patient.nin'),
            __('patient.address'),
            __('patient.blood_type'),
            __('patient.drug_allergy'),
            __('patient.profession'),
            __('patient.next_of_kin'),
            __('patient.next_of_kin_phone'),
            __('patient.ethnicity'),
            __('patient.marital_status'),
            __('patient.notes_short'),
        ];
    }

    public function array(): array
    {
        return [[
            '张三', '男', '13800138000', '1990-01-15', '', '',
            '', '', 'A型', '', '', '', '', '汉族', '已婚', '',
        ]];
    }
}
