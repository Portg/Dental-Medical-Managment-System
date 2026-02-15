<?php

namespace App\Services;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\InsuranceCompany;
use App\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PatientService
{
    /**
     * Optional fields shared by store/update.
     */
    private const OPTIONAL_FIELDS = [
        'dob' => 'date_of_birth',
        'age' => 'age',
        'ethnicity' => 'ethnicity',
        'marital_status' => 'marital_status',
        'education' => 'education',
        'blood_type' => 'blood_type',
        'email' => 'email',
        'phone_no' => 'phone_no',
        'alternative_no' => 'alternative_no',
        'address' => 'address',
        'medication_history' => 'medication_history',
        'nin' => 'nin',
        'profession' => 'profession',
        'next_of_kin' => 'next_of_kin',
        'next_of_kin_no' => 'next_of_kin_no',
        'next_of_kin_address' => 'next_of_kin_address',
        'insurance_company_id' => 'insurance_company_id',
        'source_id' => 'source_id',
        'notes' => 'notes',
    ];

    /**
     * Health-info array fields (use has() instead of filled()).
     */
    private const HEALTH_ARRAY_FIELDS = [
        'drug_allergies',
        'systemic_diseases',
    ];

    /**
     * Health-info text fields.
     */
    private const HEALTH_TEXT_FIELDS = [
        'drug_allergies_other',
        'systemic_diseases_other',
        'current_medication',
    ];

    /**
     * Get filtered patient list for DataTables.
     */
    public function getPatientList(array $filters): Collection
    {
        $query = DB::table('patients')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
            ->leftJoin('patient_sources', 'patient_sources.id', 'patients.source_id')
            ->leftJoin('users', 'users.id', 'patients._who_added')
            ->whereNull('patients.deleted_at')
            ->select(
                'patients.*', 'patients.surname', 'patients.othername',
                'insurance_companies.name', 'patient_sources.name as source_name',
                'users.surname as addedBy'
            );

        // Quick search filter (from custom search box)
        if (!empty($filters['quick_search'])) {
            $search = $filters['quick_search'];
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'patients');
                $q->orWhere('patients.phone_no', 'like', '%' . $search . '%')
                  ->orWhere('patients.patient_no', 'like', '%' . $search . '%');
            });
        }

        // DataTables default search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            if (is_array($search) && !empty($search['value'])) {
                $searchValue = $search['value'];
                $query->where(function ($q) use ($searchValue) {
                    NameHelper::addNameSearch($q, $searchValue, 'patients');
                    $q->orWhere('patients.phone_no', 'like', '%' . $searchValue . '%');
                });
            }
        }

        // Date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE(patients.created_at)'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        }

        // Insurance company filter
        if (!empty($filters['insurance_company'])) {
            $query->where('patients.insurance_company_id', $filters['insurance_company']);
        }

        // Source filter
        if (!empty($filters['filter_source'])) {
            $query->where('patients.source_id', $filters['filter_source']);
        }

        // Tags filter
        if (!empty($filters['filter_tags']) && is_array($filters['filter_tags'])) {
            $tagIds = $filters['filter_tags'];
            $query->whereIn('patients.id', function ($subquery) use ($tagIds) {
                $subquery->select('patient_id')
                    ->from('patient_tag_pivot')
                    ->whereIn('tag_id', $tagIds);
            });
        }

        return $query->orderBy('patients.id', 'desc')->get();
    }

    /**
     * Get patient data for export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('patients')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
            ->select('patients.*', 'insurance_companies.name as insurance_company');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(patients.created_at)'), [$from, $to]);
        }

        return $query->orderBy('created_at', 'ASC')->get();
    }

    /**
     * Search patients by keyword.
     *
     * @return Collection|array
     */
    public function searchPatients(string $keyword, bool $fullData = false)
    {
        $patients = Patient::where(function ($query) use ($keyword) {
                NameHelper::addNameSearch($query, $keyword);
                $query->orWhere('phone_no', 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")
                    ->orWhere('patient_no', 'LIKE', "%$keyword%");
            })
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();

        if ($fullData) {
            return $patients;
        }

        return $patients->map(function ($tag) {
            return ['id' => $tag->id, 'text' => $tag->full_name];
        })->values()->toArray();
    }

    /**
     * Get patient medical history (treatments).
     */
    public function getMedicalHistory(int $patientId): array
    {
        $medicalHistory = DB::table('treatments')
            ->leftJoin('appointments', 'appointments.id', 'treatments.appointment_id')
            ->leftJoin('users', 'users.id', 'treatments._who_added')
            ->whereNull('treatments.deleted_at')
            ->where('appointments.patient_id', $patientId)
            ->orderBy('treatments.updated_at', 'desc')
            ->select('treatments.id', 'clinical_notes', 'treatment', 'treatments.created_at')
            ->get()
            ->toArray();

        $patient = Patient::findOrFail($patientId);

        return ['patientInfor' => $patient, 'treatmentHistory' => $medicalHistory];
    }

    /**
     * Get patient detail with related data counts.
     */
    public function getPatientDetail(int $id): array
    {
        $patient = Patient::with(['InsuranceCompany'])->findOrFail($id);

        $appointmentsCount = DB::table('appointments')
            ->where('patient_id', $id)->whereNull('deleted_at')->count();

        $medicalCasesCount = DB::table('medical_cases')
            ->where('patient_id', $id)->whereNull('deleted_at')->count();

        $imagesCount = DB::table('patient_images')
            ->where('patient_id', $id)->whereNull('deleted_at')->count();

        $followupsCount = DB::table('patient_followups')
            ->where('patient_id', $id)->whereNull('deleted_at')->count();

        $invoicesCount = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->where('appointments.patient_id', $id)
            ->whereNull('invoices.deleted_at')
            ->count();

        return compact(
            'patient', 'appointmentsCount', 'medicalCasesCount',
            'imagesCount', 'followupsCount', 'invoicesCount'
        );
    }

    /**
     * Get patient data for the edit form.
     */
    public function getPatientForEdit(int $id): array
    {
        $patient = Patient::with(['patientTags', 'source'])->where('id', $id)->first();

        $company = '';
        if ($patient->insurance_company_id != null) {
            $row = InsuranceCompany::where('id', $patient->insurance_company_id)->first();
            $company = $row->name;
        }

        $source = $patient->source
            ? ['id' => $patient->source->id, 'name' => $patient->source->name]
            : null;

        $tags = $patient->patientTags->map(function ($tag) {
            return ['id' => $tag->id, 'name' => $tag->name];
        });

        return [
            'patient' => $patient,
            'company' => $company,
            'source' => $source,
            'tags' => $tags,
        ];
    }

    /**
     * Validate input and parse name parts (locale-adaptive).
     * Throws ValidationException on failure.
     *
     * @return array{surname: string, othername: string}
     */
    public function validateAndParseInput(array $input): array
    {
        if (!empty($input['full_name'])) {
            Validator::make($input, [
                'full_name' => 'required|min:2',
                'gender' => 'required',
                'telephone' => 'required',
            ], [
                'full_name.required' => __('validation.required', ['attribute' => __('patient.full_name')]),
                'gender.required' => __('validation.required', ['attribute' => __('patient.gender')]),
                'telephone.required' => __('validation.required', ['attribute' => __('patient.phone_no')]),
            ])->validate();

            return NameHelper::split($input['full_name']);
        }

        Validator::make($input, [
            'surname' => 'required',
            'othername' => 'required',
            'gender' => 'required',
            'telephone' => 'required',
        ], [
            'surname.required' => __('validation.required', ['attribute' => __('patient.surname')]),
            'othername.required' => __('validation.required', ['attribute' => __('patient.othername')]),
            'gender.required' => __('validation.required', ['attribute' => __('patient.gender')]),
            'telephone.required' => __('validation.required', ['attribute' => __('patient.phone_no')]),
        ])->validate();

        return ['surname' => $input['surname'], 'othername' => $input['othername']];
    }

    /**
     * Build the patient data array from input.
     *
     * @param array $input     Raw request input
     * @param array $nameParts Result from validateAndParseInput()
     * @param bool  $isUpdate  When true, empty optional fields are set to null (allow clearing)
     */
    public function buildPatientData(array $input, array $nameParts, bool $isUpdate = false): array
    {
        $data = [
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'gender' => $input['gender'],
            'phone_no' => $input['telephone'],
        ];

        if (!$isUpdate) {
            $data['patient_no'] = Patient::PatientNumber();
            $data['_who_added'] = Auth::User()->id;
        }

        // Optional fields
        foreach (self::OPTIONAL_FIELDS as $inputKey => $dbColumn) {
            if ($isUpdate) {
                $data[$dbColumn] = !empty($input[$inputKey]) ? $input[$inputKey] : null;
            } else {
                if (!empty($input[$inputKey])) {
                    $data[$dbColumn] = $input[$inputKey];
                }
            }
        }

        // Health array fields (drug_allergies, systemic_diseases)
        foreach (self::HEALTH_ARRAY_FIELDS as $field) {
            if ($isUpdate) {
                $data[$field] = array_key_exists($field, $input) ? $input[$field] : [];
            } else {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
        }

        // Health text fields
        foreach (self::HEALTH_TEXT_FIELDS as $field) {
            if ($isUpdate) {
                $data[$field] = !empty($input[$field]) ? $input[$field] : null;
            } else {
                if (!empty($input[$field])) {
                    $data[$field] = $input[$field];
                }
            }
        }

        // Boolean fields
        $data['is_pregnant'] = array_key_exists('is_pregnant', $input) ? true : false;
        $data['is_breastfeeding'] = array_key_exists('is_breastfeeding', $input) ? true : false;

        return $data;
    }

    /**
     * Create a new patient and optionally sync tags.
     */
    public function createPatient(array $data, ?array $tagIds = null): ?Patient
    {
        $patient = Patient::create($data);

        if ($patient && $tagIds !== null) {
            $patient->patientTags()->sync($tagIds);
        }

        return $patient;
    }

    /**
     * Update an existing patient and sync tags.
     */
    public function updatePatient(int $id, array $data, ?array $tagIds = null): bool
    {
        $status = Patient::where('id', $id)->update($data);

        $patient = Patient::find($id);
        if ($patient) {
            $patient->patientTags()->sync($tagIds ?? []);
        }

        return (bool) $status;
    }

    /**
     * Delete a patient (soft-delete).
     */
    public function deletePatient(int $id): bool
    {
        return (bool) Patient::where('id', $id)->delete();
    }
}
