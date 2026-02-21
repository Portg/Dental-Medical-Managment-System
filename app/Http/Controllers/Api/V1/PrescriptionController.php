<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PrescriptionResource;
use App\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Prescriptions
 */
class PrescriptionController extends ApiController
{
    public function __construct(
        protected PrescriptionService $service
    ) {
        $this->middleware('can:manage-treatments');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Prescription::with(['patient', 'doctor'])->whereNull('deleted_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('appointment_id')) {
            $query->where('appointment_id', $request->input('appointment_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, PrescriptionResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $prescription = Prescription::with(['patient', 'doctor', 'items'])->find($id);

        if (!$prescription) {
            return $this->error('Prescription not found', 404);
        }

        return $this->success(new PrescriptionResource($prescription));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'drug'             => 'required|string|max:255',
            'qty'              => 'required|string|max:100',
            'directions'       => 'required|string',
            'appointment_id'   => 'required|exists:appointments,id',
            'status'           => 'nullable|string|max:50',
            'prescription_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date',
            'refills_allowed'  => 'nullable|integer|min:0',
            'doctor_signature' => 'nullable|string',
            'notes'            => 'nullable|string',
            'medical_case_id'  => 'nullable|exists:medical_cases,id',
            'patient_id'       => 'nullable|exists:patients,id',
            'doctor_id'        => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only([
            'drug', 'qty', 'directions', 'appointment_id', 'status',
            'prescription_date', 'expiry_date', 'refills_allowed',
            'doctor_signature', 'notes', 'medical_case_id', 'patient_id', 'doctor_id',
        ]);
        $data['prescription_no'] = Prescription::generatePrescriptionNo();
        $data['_who_added'] = Auth::id();

        $prescription = Prescription::create($data);

        $prescription->load(['patient', 'doctor', 'items']);

        return $this->success(new PrescriptionResource($prescription), 'Prescription created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'drug'       => 'required|string|max:255',
            'qty'        => 'required|string|max:100',
            'directions' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updatePrescription($id, $request->only(['drug', 'qty', 'directions']));

        if (!$status) {
            return $this->error('Failed to update prescription', 500);
        }

        $prescription = Prescription::with(['patient', 'doctor', 'items'])->find($id);

        return $this->success(new PrescriptionResource($prescription), 'Prescription updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deletePrescription($id);

        if (!$status) {
            return $this->error('Failed to delete prescription', 500);
        }

        return $this->success(null, 'Prescription deleted');
    }

    public function byAppointment(int $appointmentId): JsonResponse
    {
        $prescriptions = $this->service->getPrescriptionsByAppointment($appointmentId);

        return $this->success(PrescriptionResource::collection($prescriptions));
    }

    public function drugNames(): JsonResponse
    {
        $names = $this->service->getAllDrugNames();

        return $this->success($names);
    }
}
