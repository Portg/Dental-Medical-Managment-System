<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PatientResource;
use App\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatientController extends ApiController
{
    public function __construct(
        protected PatientService $patientService
    ) {
        $this->middleware('can:view-patients')->only(['index', 'show', 'search', 'medicalHistory']);
        $this->middleware('can:create-patients')->only(['store']);
        $this->middleware('can:edit-patients')->only(['update']);
        $this->middleware('can:delete-patients')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Patient::whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('surname', 'like', "%{$search}%")
                  ->orWhere('othername', 'like', "%{$search}%")
                  ->orWhere('phone_no', 'like', "%{$search}%")
                  ->orWhere('patient_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, PatientResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $detail = $this->patientService->getPatientDetail($id);

        return $this->success([
            'patient' => new PatientResource($detail['patient']),
            'counts'  => [
                'appointments'  => $detail['appointmentsCount'],
                'medical_cases' => $detail['medicalCasesCount'],
                'images'        => $detail['imagesCount'],
                'followups'     => $detail['followupsCount'],
                'invoices'      => $detail['invoicesCount'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $patientFields = $request->only([
            'full_name', 'surname', 'othername', 'gender', 'telephone',
            'dob', 'age', 'ethnicity', 'marital_status', 'education', 'blood_type',
            'email', 'phone_no', 'alternative_no', 'address', 'medication_history',
            'nin', 'profession', 'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
            'insurance_company_id', 'source_id', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);

        try {
            $nameParts = $this->patientService->validateAndParseInput($patientFields);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        }

        $data    = $this->patientService->buildPatientData($patientFields, $nameParts, false);
        $patient = $this->patientService->createPatient($data, $request->input('tags'));

        if (!$patient) {
            return $this->error('Failed to create patient', 500);
        }

        return $this->success(new PatientResource($patient), 'Patient created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $patientFields = $request->only([
            'full_name', 'surname', 'othername', 'gender', 'telephone',
            'dob', 'age', 'ethnicity', 'marital_status', 'education', 'blood_type',
            'email', 'phone_no', 'alternative_no', 'address', 'medication_history',
            'nin', 'profession', 'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
            'insurance_company_id', 'source_id', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);

        try {
            $nameParts = $this->patientService->validateAndParseInput($patientFields);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        }

        $data   = $this->patientService->buildPatientData($patientFields, $nameParts, true);
        $status = $this->patientService->updatePatient($id, $data, $request->input('tags'));

        if (!$status) {
            return $this->error('Failed to update patient', 500);
        }

        $patient = Patient::find($id);

        return $this->success(new PatientResource($patient), 'Patient updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->patientService->deletePatient($id);

        if (!$status) {
            return $this->error('Failed to delete patient', 500);
        }

        return $this->success(null, 'Patient deleted');
    }

    public function search(Request $request): JsonResponse
    {
        $keyword  = $request->input('q', '');
        $patients = $this->patientService->searchPatients($keyword, true);

        return $this->success(PatientResource::collection($patients));
    }

    public function medicalHistory(int $id): JsonResponse
    {
        $data = $this->patientService->getMedicalHistory($id);

        return $this->success($data);
    }
}
