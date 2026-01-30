<?php

namespace App\Http\Controllers;

use App\DoctorSchedule;
use App\User;
use App\Branch;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class DoctorScheduleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('doctor_schedules')
                ->leftJoin('users as doctor', 'doctor.id', 'doctor_schedules.doctor_id')
                ->leftJoin('users as creator', 'creator.id', 'doctor_schedules._who_added')
                ->leftJoin('branches', 'branches.id', 'doctor_schedules.branch_id')
                ->whereNull('doctor_schedules.deleted_at')
                ->select([
                    'doctor_schedules.*',
                    'doctor.surname as doctor_name',
                    'creator.surname as added_by',
                    'branches.name as branch_name'
                ])
                ->orderBy('doctor_schedules.schedule_date', 'desc')
                ->get();

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

        $doctors = User::where('is_doctor', 'Yes')->whereNull('deleted_at')->get();
        $branches = Branch::whereNull('deleted_at')->get();

        return view('doctor_schedules.index', compact('doctors', 'branches'));
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

        $data = [
            'doctor_id' => $request->doctor_id,
            'schedule_date' => $request->schedule_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_recurring' => $request->is_recurring ? true : false,
            'recurring_pattern' => $request->is_recurring ? $request->recurring_pattern : null,
            'recurring_until' => $request->is_recurring ? $request->recurring_until : null,
            'max_patients' => $request->max_patients,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
            '_who_added' => Auth::user()->id,
        ];

        $success = DoctorSchedule::create($data);

        // Generate recurring schedules if needed
        if ($request->is_recurring && $request->recurring_pattern && $request->recurring_until) {
            $this->generateRecurringSchedules($data, $request->recurring_until);
        }

        return FunctionsHelper::messageResponse(__('doctor_schedules.added_successfully'), $success);
    }

    public function edit($id)
    {
        $schedule = DoctorSchedule::where('id', $id)->first();
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

        $success = DoctorSchedule::where('id', $id)->update([
            'doctor_id' => $request->doctor_id,
            'schedule_date' => $request->schedule_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_recurring' => $request->is_recurring ? true : false,
            'recurring_pattern' => $request->is_recurring ? $request->recurring_pattern : null,
            'recurring_until' => $request->is_recurring ? $request->recurring_until : null,
            'max_patients' => $request->max_patients,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
            'changed_by' => Auth::user()->id,
        ]);

        return FunctionsHelper::messageResponse(__('doctor_schedules.updated_successfully'), $success);
    }

    public function destroy($id)
    {
        $success = DoctorSchedule::where('id', $id)->delete();
        return FunctionsHelper::messageResponse(__('doctor_schedules.deleted_successfully'), $success);
    }

    /**
     * Get doctor schedules for calendar view
     */
    public function calendar(Request $request)
    {
        $start = $request->start ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $request->end ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $schedules = DoctorSchedule::with('doctor')
            ->whereBetween('schedule_date', [$start, $end])
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->doctor->surname . ' (' . substr($schedule->start_time, 0, 5) . '-' . substr($schedule->end_time, 0, 5) . ')',
                    'start' => $schedule->schedule_date->format('Y-m-d') . 'T' . $schedule->start_time,
                    'end' => $schedule->schedule_date->format('Y-m-d') . 'T' . $schedule->end_time,
                    'color' => $this->getDoctorColor($schedule->doctor_id),
                ];
            });

        return response()->json($schedules);
    }

    /**
     * Generate recurring schedules
     */
    private function generateRecurringSchedules($data, $until)
    {
        $currentDate = Carbon::parse($data['schedule_date']);
        $endDate = Carbon::parse($until);
        $pattern = $data['recurring_pattern'];

        while ($currentDate->lt($endDate)) {
            switch ($pattern) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }

            if ($currentDate->lte($endDate)) {
                $newData = $data;
                $newData['schedule_date'] = $currentDate->format('Y-m-d');
                DoctorSchedule::create($newData);
            }
        }
    }

    /**
     * Get a consistent color for each doctor
     */
    private function getDoctorColor($doctorId)
    {
        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];
        return $colors[$doctorId % count($colors)];
    }
}
