<?php

namespace App\Http\Controllers;

use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    private ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
        $this->middleware('can:manage-shifts');
    }

    public function index(): JsonResponse
    {
        $shifts = $this->shiftService->getAllShifts();
        return response()->json(['status' => 1, 'data' => $shifts]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:20',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'work_status'  => 'required|in:on_duty,rest',
            'color'        => 'required|string|max:7',
            'max_patients' => 'required|integer|min:0|max:' . \App\SystemSetting::get('schedule.max_patients_upper_limit', 50),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $shift = $this->shiftService->createShift($request->only([
            'name', 'start_time', 'end_time', 'work_status', 'color', 'max_patients',
        ]));

        return response()->json([
            'status'  => 1,
            'message' => __('shifts.added_successfully'),
            'data'    => $shift,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:20',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'work_status'  => 'required|in:on_duty,rest',
            'color'        => 'required|string|max:7',
            'max_patients' => 'required|integer|min:0|max:' . \App\SystemSetting::get('schedule.max_patients_upper_limit', 50),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $this->shiftService->updateShift($id, $request->only([
            'name', 'start_time', 'end_time', 'work_status', 'color', 'max_patients',
        ]));

        return response()->json([
            'status'  => 1,
            'message' => __('shifts.updated_successfully'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->shiftService->deleteShift($id);

        return response()->json([
            'status'  => $result['success'] ? 1 : 0,
            'message' => $result['message'],
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()]);
        }

        $this->shiftService->reorder($request->input('ids'));

        return response()->json(['status' => 1, 'message' => __('shifts.reorder_successfully')]);
    }
}
