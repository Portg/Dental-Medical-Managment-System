<?php

namespace App\Imports;

use App\Http\Helper\NameHelper;
use App\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PatientImport implements ToCollection, WithStartRow
{
    private int $successCount = 0;
    private array $failures = [];

    private const MAX_ROWS = 500;

    // Column index mapping (0-based)
    private const COL_NAME = 0;
    private const COL_GENDER = 1;
    private const COL_PHONE = 2;
    private const COL_DOB = 3;
    private const COL_ALT_PHONE = 4;
    private const COL_EMAIL = 5;
    private const COL_NIN = 6;
    private const COL_ADDRESS = 7;
    private const COL_BLOOD_TYPE = 8;
    private const COL_DRUG_ALLERGY = 9;
    private const COL_PROFESSION = 10;
    private const COL_NEXT_OF_KIN = 11;
    private const COL_NEXT_OF_KIN_PHONE = 12;
    private const COL_ETHNICITY = 13;
    private const COL_MARITAL_STATUS = 14;
    private const COL_NOTES = 15;

    /**
     * Skip the header row; data starts from row 2.
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        if ($rows->count() > self::MAX_ROWS) {
            $this->failures[] = [
                'row' => 0,
                'errors' => [__('patient.import_exceed_limit', ['limit' => self::MAX_ROWS])],
            ];
            return;
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // actual Excel row number (1-based header + 1-based index)

            // Skip completely empty rows
            $values = $row->filter(fn($v) => $v !== null && trim((string) $v) !== '');
            if ($values->isEmpty()) {
                continue;
            }

            // Validate required fields
            $errors = $this->validateRow($row);
            if (!empty($errors)) {
                $this->failures[] = ['row' => $rowNumber, 'errors' => $errors];
                continue;
            }

            try {
                $this->createPatient($row);
                $this->successCount++;
            } catch (\Exception $e) {
                Log::warning('Patient import row failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
                $this->failures[] = [
                    'row' => $rowNumber,
                    'errors' => [$e->getMessage()],
                ];
            }
        }
    }

    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'failures' => $this->failures,
        ];
    }

    private function validateRow(Collection $row): array
    {
        $errors = [];

        $name = trim((string) ($row[self::COL_NAME] ?? ''));
        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = __('validation.required', ['attribute' => __('patient.full_name')]);
        }

        $gender = trim((string) ($row[self::COL_GENDER] ?? ''));
        if ($gender === '') {
            $errors[] = __('validation.required', ['attribute' => __('patient.gender')]);
        } elseif (!$this->mapGender($gender)) {
            $errors[] = __('patient.import_invalid_gender');
        }

        $phone = trim((string) ($row[self::COL_PHONE] ?? ''));
        if ($phone === '') {
            $errors[] = __('validation.required', ['attribute' => __('patient.phone_no')]);
        }

        return $errors;
    }

    private function createPatient(Collection $row): void
    {
        $fullName = trim((string) $row[self::COL_NAME]);
        $nameParts = NameHelper::split($fullName);

        $data = [
            'patient_no' => Patient::PatientNumber(),
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'gender' => $this->mapGender(trim((string) $row[self::COL_GENDER])),
            'phone_no' => trim((string) $row[self::COL_PHONE]),
            '_who_added' => Auth::id(),
        ];

        // Optional fields
        $dob = $this->parseDate($row[self::COL_DOB] ?? null);
        if ($dob) {
            $data['date_of_birth'] = $dob;
        }

        $optionalText = [
            self::COL_ALT_PHONE => 'alternative_no',
            self::COL_EMAIL => 'email',
            self::COL_NIN => 'nin',
            self::COL_ADDRESS => 'address',
            self::COL_DRUG_ALLERGY => 'drug_allergies_other',
            self::COL_PROFESSION => 'profession',
            self::COL_NEXT_OF_KIN => 'next_of_kin',
            self::COL_NEXT_OF_KIN_PHONE => 'next_of_kin_no',
            self::COL_NOTES => 'notes',
        ];

        foreach ($optionalText as $colIndex => $dbField) {
            $value = trim((string) ($row[$colIndex] ?? ''));
            if ($value !== '') {
                $data[$dbField] = $value;
            }
        }

        // Blood type mapping
        $bloodType = trim((string) ($row[self::COL_BLOOD_TYPE] ?? ''));
        if ($bloodType !== '') {
            $data['blood_type'] = $this->mapBloodType($bloodType);
        }

        // Ethnicity mapping
        $ethnicity = trim((string) ($row[self::COL_ETHNICITY] ?? ''));
        if ($ethnicity !== '') {
            $data['ethnicity'] = $this->mapEthnicity($ethnicity);
        }

        // Marital status mapping
        $maritalStatus = trim((string) ($row[self::COL_MARITAL_STATUS] ?? ''));
        if ($maritalStatus !== '') {
            $data['marital_status'] = $this->mapMaritalStatus($maritalStatus);
        }

        Patient::create($data);
    }

    /**
     * Map gender input to DB value.
     * Accepts: 男/Male/male/女/Female/female
     */
    private function mapGender(string $input): ?string
    {
        $map = [
            '男' => 'Male',
            'male' => 'Male',
            'm' => 'Male',
            '女' => 'Female',
            'female' => 'Female',
            'f' => 'Female',
        ];

        return $map[mb_strtolower($input)] ?? null;
    }

    /**
     * Map blood type input to DB key.
     * Accepts: A/A型/B/B型/AB/AB型/O/O型
     */
    private function mapBloodType(string $input): string
    {
        // Strip trailing '型'
        $normalized = str_replace('型', '', $input);
        $normalized = trim($normalized);

        // Check if it's a valid key in bloodTypeOptions
        if (isset(Patient::$bloodTypeOptions[$normalized])) {
            return $normalized;
        }

        // Try matching by value (Chinese label)
        $flipped = array_flip(Patient::$bloodTypeOptions);
        if (isset($flipped[$input])) {
            return $flipped[$input];
        }

        // Return as-is if no match
        return $input;
    }

    /**
     * Map ethnicity input to DB key.
     * Accepts: Chinese value (e.g., 汉族) or key (e.g., han)
     */
    private function mapEthnicity(string $input): string
    {
        // Check if it's already a valid key
        if (isset(Patient::$ethnicityOptions[$input])) {
            return $input;
        }

        // Try matching by value (Chinese label)
        $flipped = array_flip(Patient::$ethnicityOptions);
        if (isset($flipped[$input])) {
            return $flipped[$input];
        }

        return $input;
    }

    /**
     * Map marital status input to DB key.
     * Accepts: Chinese value (e.g., 已婚) or key (e.g., married)
     */
    private function mapMaritalStatus(string $input): string
    {
        if (isset(Patient::$maritalStatusOptions[$input])) {
            return $input;
        }

        $flipped = array_flip(Patient::$maritalStatusOptions);
        if (isset($flipped[$input])) {
            return $flipped[$input];
        }

        return $input;
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        // Handle Excel numeric date serial
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Try standard date formats
        $formats = ['Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y', 'Y.m.d'];
        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed && $parsed->format($format) === $value) {
                return $parsed->format('Y-m-d');
            }
        }

        // Last resort: strtotime
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }
}
