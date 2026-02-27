<?php

namespace App\Services;

use App\Appointment;
use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\InsuranceCompany;
use App\MedicalCase;
use App\Patient;
use App\PatientFollowup;
use App\PatientImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

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
        'referred_by' => 'referred_by',
        'patient_group' => 'patient_group',
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
                'users.surname as addedBy',
                DB::raw("(SELECT GROUP_CONCAT(pt.name SEPARATOR ', ') FROM patient_tag_pivot ptp JOIN patient_tags pt ON pt.id = ptp.tag_id WHERE ptp.patient_id = patients.id) as tags_badges")
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

        // Group filter (sidebar)
        if (!empty($filters['filter_group'])) {
            $query->where('patients.patient_group', $filters['filter_group']);
        }

        // Sidebar tag filter (single tag click)
        if (!empty($filters['filter_sidebar_tag'])) {
            $query->whereIn('patients.id', function ($subquery) use ($filters) {
                $subquery->select('patient_id')
                    ->from('patient_tag_pivot')
                    ->where('tag_id', $filters['filter_sidebar_tag']);
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
     * Get group sidebar data for the patient list page.
     */
    public function getGroupSidebarData(): array
    {
        $totalCount = Patient::count();

        // 从字典表读取分组选项
        $allGroups = \App\DictItem::ofType('patient_group')->active()->ordered()->get();

        $groupCounts = Patient::select('patient_group', DB::raw('count(*) as cnt'))
            ->whereNotNull('patient_group')
            ->where('patient_group', '!=', '')
            ->groupBy('patient_group')
            ->pluck('cnt', 'patient_group')
            ->toArray();

        $tagCounts = DB::table('patient_tag_pivot')
            ->join('patient_tags', 'patient_tags.id', '=', 'patient_tag_pivot.tag_id')
            ->select('patient_tags.id', 'patient_tags.name', DB::raw('count(*) as cnt'))
            ->groupBy('patient_tags.id', 'patient_tags.name')
            ->get();

        return compact('totalCount', 'allGroups', 'groupCounts', 'tagCounts');
    }

    /**
     * Get patient detail with related data counts.
     */
    public function getPatientDetail(int $id): array
    {
        $patient = Patient::with([
            'InsuranceCompany', 'patientTags', 'source', 'referrer',
            'sharedHolders.sharedPatient', 'memberLevel',
        ])->findOrFail($id);

        $appointmentsCount = Appointment::where('patient_id', $id)->count();

        $medicalCasesCount = MedicalCase::where('patient_id', $id)->count();

        $imagesCount = PatientImage::where('patient_id', $id)->count();

        $followupsCount = PatientFollowup::where('patient_id', $id)->count();

        $invoicesCount = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->where('appointments.patient_id', $id)
            ->whereNull('invoices.deleted_at')
            ->whereNull('appointments.deleted_at')
            ->count();

        // 首诊信息 (first visit)
        $firstVisit = Appointment::with('doctor')
            ->where('patient_id', $id)
            ->orderBy('start_date', 'asc')
            ->first();

        // 最新就诊 (latest visit)
        $latestVisit = Appointment::with('doctor')
            ->where('patient_id', $id)
            ->orderBy('start_date', 'desc')
            ->first();

        // 消费总额 (total spending)
        $totalSpending = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->where('appointments.patient_id', $id)
            ->whereNull('invoices.deleted_at')
            ->whereNull('appointments.deleted_at')
            ->sum('invoices.total_amount');

        // 所有标签（用于左侧面板复选框）
        $allTags = \App\PatientTag::orderBy('name')->get(['id', 'name']);

        // 所有分组（用于左侧面板单选）
        $allGroups = \App\DictItem::ofType('patient_group')->active()->ordered()->get();

        return compact(
            'patient', 'appointmentsCount', 'medicalCasesCount',
            'imagesCount', 'followupsCount', 'invoicesCount',
            'firstVisit', 'latestVisit', 'totalSpending', 'allTags', 'allGroups'
        );
    }

    /**
     * Get patient data for the edit form.
     */
    public function getPatientForEdit(int $id): array
    {
        $patient = Patient::with(['patientTags', 'source', 'referrer', 'sharedHolders.sharedPatient'])->where('id', $id)->first();

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
     * Sync kin relations (shared holders) for a patient.
     */
    public function syncKinRelations(int $patientId, ?array $kinRelations = null): void
    {
        if ($kinRelations === null) {
            return;
        }

        // Remove existing shared holders for this patient
        \App\MemberSharedHolder::where('primary_patient_id', $patientId)->delete();

        // Add new ones
        foreach ($kinRelations as $relation) {
            if (empty($relation['patient_id'])) {
                continue;
            }
            \App\MemberSharedHolder::create([
                'primary_patient_id' => $patientId,
                'shared_patient_id' => $relation['patient_id'],
                'relationship' => $relation['relationship'] ?? null,
                'is_active' => true,
                '_who_added' => \Illuminate\Support\Facades\Auth::id(),
            ]);
        }
    }

    /**
     * Delete a patient (soft-delete).
     */
    public function deletePatient(int $id): bool
    {
        return (bool) Patient::where('id', $id)->delete();
    }

    /**
     * Build the DataTables response for the patient index listing.
     */
    public function buildIndexDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->filter(function ($instance) {
            })
            ->addColumn('full_name', function ($row) {
                $fullName = NameHelper::join($row->surname, $row->othername);
                return DataMaskingService::maskName($fullName);
            })
            ->addColumn('gender', function ($row) {
                if ($row->gender == 'Male') {
                    return __('patient.male');
                } elseif ($row->gender == 'Female') {
                    return __('patient.female');
                }
                return $row->gender ?: '';
            })
            ->addColumn('patient_no', function ($row) {
                return '<a href="#"> ' . $row->patient_no . '</a>';
            })
            ->addColumn('phone_no', function ($row) {
                return DataMaskingService::maskPhone($row->phone_no);
            })
            ->addColumn('tags_badges', function ($row) {
                return $row->tags_badges ?: '';
            })
            ->addColumn('source_name', function ($row) {
                return $row->source_name ?: '';
            })
            ->addColumn('medical_insurance', function ($row) {
                if ($row->has_insurance && $row->insurance_company_id != null) {
                    return $row->name;
                } elseif ($row->has_insurance) {
                    return __('common.yes');
                } else {
                    return __('common.no');
                }
            })
            ->addColumn('Medical_History', function ($row) {
                return '<a href="' . url('/medical-history/' . $row->id) . '" class="btn btn-success">' . __('patient.medical_history') . '</a>';
            })
            ->addColumn('addedBy', function ($row) {
                return $row->addedBy;
            })
            ->addColumn('status', function ($row) {
                if ($row->deleted_at != null) {
                    return '<span class="text-danger">' . __('common.inactive') . '</span>';
                } else {
                    return '<span class="text-primary">' . __('common.active') . '</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return '
                  <div class="btn-group">
                    <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                            aria-expanded="false"> ' . __('common.action') . '
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li>
                            <a href="' . url('patients/' . $row->id) . '">' . __('patient.patient_details') . '</a>
                        </li>
                        <li>
                            <a href="#" onclick="editRecord(' . $row->id . ')">' . __('patient.patient_profile') . '</a>
                        </li>
                         <li>
                           <a href="#" onclick="getPatientMedicalHistory(' . $row->id . ')" >' . __('patient.patient_history') . '</a>
                        </li>
                         <li>
                           <a href="#" onclick="deleteRecord(' . $row->id . ')" >' . __('patient.delete_patient') . '</a>
                        </li>
                    </ul>
                </div>
                ';
            })
            ->rawColumns(['patient_no', 'medical_insurance', 'Medical_History', 'status', 'action'])
            ->make(true);
    }
}
