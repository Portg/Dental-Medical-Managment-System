<?php

namespace App\Http\Controllers;

use App\Services\DoctorScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DoctorScheduleController extends Controller
{
    private DoctorScheduleService $service;

    public function __construct(DoctorScheduleService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-schedules')->except(['index', 'gridData', 'create', 'show']);
        $this->middleware(function ($req, $next) {
            if (!auth()->user()->can('manage-schedules') &&
                !auth()->user()->can('view-all-schedules') &&
                !auth()->user()->can('view-own-schedule')) {
                abort(403);
            }
            return $next($req);
        })->only(['index', 'gridData']);
    }

    /**
     * Monthly grid view.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $doctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;
            $data = $this->service->getListSchedules($doctorId);

            return $this->service->buildIndexDataTable($data);
        }

        $viewData = $this->service->getGridViewData();
        return view('doctor_schedules.index', $viewData);
    }

    /**
     * AJAX: Get schedule data for a given month.
     */
    public function gridData(Request $request): JsonResponse
    {
        $yearMonth = $request->input('month', now()->format('Y-m'));
        $grid = $this->service->getMonthSchedules($yearMonth);

        return response()->json(['status' => 1, 'data' => $grid]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $doctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;
        $events = $this->service->getCalendarEvents(
            $request->input('start'),
            $request->input('end'),
            $doctorId
        );

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer|exists:users,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'required|integer|min:1',
            'is_recurring' => 'nullable|boolean',
            'recurring_pattern' => 'nullable|in:daily,weekly,monthly',
            'recurring_until' => 'nullable|date|after_or_equal:schedule_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()], 422);
        }

        if (!Auth::user()->can('manage-schedules') && Auth::id() !== (int) $request->doctor_id) {
            return response()->json([
                'status' => 0,
                'message' => __('doctor_schedules.cannot_edit_others'),
            ], 403);
        }

        $result = $this->service->createSchedule($request->all(), (int) Auth::id());

        return response()->json([
            'status' => $result['success'] ? 1 : 0,
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    public function edit($id): JsonResponse
    {
        $schedule = $this->service->find((int) $id);
        if (!$schedule) {
            return response()->json(['message' => __('doctor_schedules.not_found')], 404);
        }

        return response()->json([
            'id' => $schedule->id,
            'doctor_id' => $schedule->doctor_id,
            'branch_id' => $schedule->branch_id,
            'schedule_date' => optional($schedule->schedule_date)->format('Y-m-d'),
            'start_time' => $schedule->start_time ?: $schedule->getEffectiveStartTime(),
            'end_time' => $schedule->end_time ?: $schedule->getEffectiveEndTime(),
            'max_patients' => $schedule->max_patients ?: $schedule->getEffectiveMaxPatients(),
            'notes' => $schedule->notes,
            'is_recurring' => (bool) $schedule->is_recurring,
            'recurring_pattern' => $schedule->recurring_pattern,
            'recurring_until' => optional($schedule->recurring_until)->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer|exists:users,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'required|integer|min:1',
            'is_recurring' => 'nullable|boolean',
            'recurring_pattern' => 'nullable|in:daily,weekly,monthly',
            'recurring_until' => 'nullable|date|after_or_equal:schedule_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()], 422);
        }

        if (!Auth::user()->can('manage-schedules') && Auth::id() !== (int) $request->doctor_id) {
            return response()->json([
                'status' => 0,
                'message' => __('doctor_schedules.cannot_edit_others'),
            ], 403);
        }

        $result = $this->service->updateSchedule((int) $id, $request->all(), (int) Auth::id());

        return response()->json([
            'status' => $result['success'] ? 1 : 0,
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->service->removeSchedule((int) $id);

        return response()->json([
            'status' => $result['success'] ? 1 : 0,
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    /**
     * AJAX: Assign a shift to doctor + date.
     */
    public function assign(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer|exists:users,id',
            'date'      => 'required|date',
            'shift_id'  => 'required|integer|exists:shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        // AG-032: Doctors can only edit their own schedule
        if (!Auth::user()->can('manage-schedules') && Auth::id() != $request->doctor_id) {
            return response()->json([
                'status'  => 0,
                'message' => __('doctor_schedules.cannot_edit_others'),
            ]);
        }

        $result = $this->service->assignShift(
            (int) $request->doctor_id,
            $request->date,
            (int) $request->shift_id
        );

        return response()->json([
            'status'  => $result['success'] ? 1 : 0,
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    /**
     * AJAX: Remove a schedule entry.
     */
    public function remove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|integer|exists:doctor_schedules,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->removeSchedule((int) $request->schedule_id);

        return response()->json([
            'status'  => $result['success'] ? 1 : 0,
            'message' => $result['message'],
        ]);
    }

    /**
     * AJAX: Copy a week's schedules.
     */
    public function copyWeek(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source_date' => 'required|date',
            'target_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->copyWeek($request->source_date, $request->target_date);

        return response()->json([
            'status'  => $result['success'] ? 1 : 0,
            'message' => $result['message'],
        ]);
    }

    /**
     * AJAX: Copy previous month's schedules.
     */
    public function copyMonth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->copyMonth($request->target_month);

        return response()->json([
            'status'  => $result['success'] ? 1 : 0,
            'message' => $result['message'],
        ]);
    }
}
