<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\DoctorScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class DoctorScheduleController extends Controller
{
    private DoctorScheduleService $service;

    public function __construct(DoctorScheduleService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-schedules');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getScheduleList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('time_range', function ($row) {
                    return substr($row->start_time, 0, 5) . ' - ' . substr($row->end_time, 0, 5);
                })
                ->addColumn('recurring_info', function ($row) {
                    if ($row->is_recurring) {
                        $pattern = __('doctor_schedules.pattern_' . $row->recurring_pattern);
                        $until = $row->recurring_until ? ' (' . __('common.until') . ' ' . $row->recurring_until . ')' : '';
                        return $pattern . $until;
                    }
                    return __('common.no');
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }

        $formData = $this->service->getFormData();

        return view('doctor_schedules.index', $formData);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'max_patients' => 'required|integer|min:1',
        ], [
            'doctor_id.required' => __('doctor_schedules.doctor_required'),
            'schedule_date.required' => __('doctor_schedules.date_required'),
            'start_time.required' => __('doctor_schedules.start_time_required'),
            'end_time.required' => __('doctor_schedules.end_time_required'),
            'end_time.after' => __('doctor_schedules.end_time_after_start'),
            'max_patients.required' => __('doctor_schedules.max_patients_required'),
        ])->validate();

        $success = $this->service->createSchedule($request->only([
            'doctor_id', 'schedule_date', 'start_time', 'end_time', 'max_patients',
            'is_recurring', 'recurring_pattern', 'recurring_until',
        ]));

        return FunctionsHelper::messageResponse(__('doctor_schedules.added_successfully'), $success);
    }

    public function edit($id)
    {
        $schedule = $this->service->find($id);
        return response()->json($schedule);
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'max_patients' => 'required|integer|min:1',
        ], [
            'doctor_id.required' => __('doctor_schedules.doctor_required'),
            'schedule_date.required' => __('doctor_schedules.date_required'),
            'start_time.required' => __('doctor_schedules.start_time_required'),
            'end_time.required' => __('doctor_schedules.end_time_required'),
            'end_time.after' => __('doctor_schedules.end_time_after_start'),
            'max_patients.required' => __('doctor_schedules.max_patients_required'),
        ])->validate();

        $success = $this->service->updateSchedule($id, $request->only([
            'doctor_id', 'schedule_date', 'start_time', 'end_time', 'max_patients',
            'is_recurring', 'recurring_pattern', 'recurring_until',
        ]));

        return FunctionsHelper::messageResponse(__('doctor_schedules.updated_successfully'), $success);
    }

    public function destroy($id)
    {
        $success = $this->service->deleteSchedule($id);
        return FunctionsHelper::messageResponse(__('doctor_schedules.deleted_successfully'), $success);
    }

    /**
     * Get doctor schedules for calendar view
     */
    public function calendar(Request $request)
    {
        $start = $request->start ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $request->end ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $schedules = $this->service->getCalendarSchedules($start, $end);

        return response()->json($schedules);
    }
}
