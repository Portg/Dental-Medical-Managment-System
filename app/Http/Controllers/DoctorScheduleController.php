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
