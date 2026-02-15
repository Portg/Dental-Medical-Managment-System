<?php

namespace App\Http\Controllers\Api\V1;

use App\Appointment;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends ApiController
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with(['patient', 'doctor', 'chair', 'service'])
            ->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_no', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn ($pq) => $pq->where('surname', 'like', "%{$search}%")
                      ->orWhere('othername', 'like', "%{$search}%")
                      ->orWhere('phone_no', 'like', "%{$search}%"));
            });
        }

        if ($doctorId = $request->input('doctor_id')) {
            $query->where('doctor_id', $doctorId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sort_by', [$request->input('start_date'), $request->input('end_date')]);
        }

        $paginator = $query->orderBy('sort_by', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, AppointmentResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'doctor', 'chair', 'service', 'branch'])
            ->findOrFail($id);

        return $this->success(new AppointmentResource($appointment));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'visit_information' => 'required',
            'appointment_date'  => 'required|date',
            'appointment_time'  => 'required',
            'patient_id'        => 'required|exists:patients,id',
            'doctor_id'         => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $appointment = $this->appointmentService->createAppointment($request->all());

        if (!$appointment) {
            return $this->error('Failed to create appointment', 500);
        }

        $appointment->load(['patient', 'doctor', 'chair', 'service']);

        return $this->success(new AppointmentResource($appointment), 'Appointment created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'visit_information' => 'required',
            'appointment_date'  => 'required|date',
            'appointment_time'  => 'required',
            'patient_id'        => 'required|exists:patients,id',
            'doctor_id'         => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->appointmentService->updateAppointment($id, $request->all());

        if (!$status) {
            return $this->error('Failed to update appointment', 500);
        }

        $appointment = Appointment::with(['patient', 'doctor', 'chair', 'service'])->find($id);

        return $this->success(new AppointmentResource($appointment), 'Appointment updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->appointmentService->deleteAppointment($id);

        if (!$status) {
            return $this->error('Failed to delete appointment', 500);
        }

        return $this->success(null, 'Appointment deleted');
    }

    public function reschedule(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->appointmentService->rescheduleAppointment($id, $request->all());

        if (!$status) {
            return $this->error('Failed to reschedule appointment', 500);
        }

        $appointment = Appointment::with(['patient', 'doctor'])->find($id);

        return $this->success(new AppointmentResource($appointment), 'Appointment rescheduled');
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $events = $this->appointmentService->getCalendarEvents(
            $request->input('start'),
            $request->input('end')
        );

        return $this->success($events);
    }

    public function chairs(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id', auth()->user()->branch_id);

        $chairs = $this->appointmentService->getChairs($branchId);

        return $this->success($chairs);
    }

    public function doctorTimeSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $slots = $this->appointmentService->getDoctorTimeSlots(
            $request->input('doctor_id'),
            $request->input('date')
        );

        return $this->success($slots);
    }
}
