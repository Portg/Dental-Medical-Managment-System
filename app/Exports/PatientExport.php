<?php

namespace App\Exports;

use App\Patient;
use App\Services\DataMaskingService;
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
        $mask = DataMaskingService::isExportMaskingEnabled();
        $rows = [];
        foreach ($this->data as $row) {
            $fullName = trim(($row->surname ?? '') . ($row->othername ?? ''));
            $gender = $row->gender === 'Male' ? __('patient.male') : ($row->gender === 'Female' ? __('patient.female') : ($row->gender ?? ''));
            $rows[] = [
                $row->patient_no ?? '',
                $mask ? DataMaskingService::maskName($fullName) : $fullName,
                $gender,
                $row->dob ?? '',
                $mask ? DataMaskingService::maskPhone($row->phone_no) : ($row->phone_no ?? ''),
                $mask ? DataMaskingService::maskPhone($row->alternative_no) : ($row->alternative_no ?? ''),
                $mask ? DataMaskingService::maskEmail($row->email) : ($row->email ?? ''),
                $mask ? DataMaskingService::maskNin($row->nin) : ($row->nin ?? ''),
                $mask ? DataMaskingService::maskAddress($row->address) : ($row->address ?? ''),
                $row->blood_type ?? '',
                $this->formatAllergies($row),
                $row->profession ?? '',
                $mask ? DataMaskingService::maskName($row->next_of_kin) : ($row->next_of_kin ?? ''),
                $mask ? DataMaskingService::maskPhone($row->next_of_kin_no) : ($row->next_of_kin_no ?? ''),
                $row->has_insurance ? __('common.yes') : __('common.no'),
                $row->insurance_company ?? '',
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            __('patient.patient_no'),
            __('patient.full_name'),
            __('patient.gender'),
            __('patient.dob'),
            __('patient.phone_no'),
            __('patient.alternative_phone_no'),
            __('patient.email_address'),
            __('patient.nin'),
            __('patient.address'),
            __('patient.blood_type'),
            __('patient.drug_allergy'),
            __('patient.profession'),
            __('patient.next_of_kin'),
            __('patient.next_of_kin_phone'),
            __('patient.has_insurance'),
            __('patient.insurance_company'),
        ];
    }

    private function formatAllergies(object $row): string
    {
        $allergies = [];
        $raw = is_string($row->drug_allergies ?? null) ? json_decode($row->drug_allergies, true) : ($row->drug_allergies ?? []);
        if (!empty($raw)) {
            foreach ($raw as $key) {
                $allergies[] = Patient::$allergyOptions[$key] ?? $key;
            }
        }
        if (!empty($row->drug_allergies_other)) {
            $allergies[] = $row->drug_allergies_other;
        }
        return implode('、', $allergies);
    }
}
