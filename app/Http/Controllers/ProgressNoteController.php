<?php

namespace App\Http\Controllers;

use App\ProgressNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ProgressNoteController extends Controller
{
    /**
     * Display a listing of the resource for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = DB::table('progress_notes')
                ->leftJoin('medical_cases', 'medical_cases.id', 'progress_notes.medical_case_id')
                ->leftJoin('appointments', 'appointments.id', 'progress_notes.appointment_id')
                ->leftJoin('users', 'users.id', 'progress_notes._who_added')
                ->whereNull('progress_notes.deleted_at')
                ->where('progress_notes.patient_id', $patient_id)
                ->orderBy('progress_notes.note_date', 'desc')
                ->select(
                    'progress_notes.*',
                    'medical_cases.case_no',
                    'medical_cases.title as case_title',
                    'appointments.appointment_no',
                    DB::raw("CONCAT(users.surname, ' ', users.othername) as added_by")
                )
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewProgressNote(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editProgressNote(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteProgressNote(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('noteTypeBadge', function ($row) {
                    $class = 'default';
                    if ($row->note_type == 'SOAP') $class = 'primary';
                    elseif ($row->note_type == 'General') $class = 'info';
                    elseif ($row->note_type == 'Follow-up') $class = 'warning';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.note_type_' . strtolower(str_replace('-', '_', $row->note_type))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'noteTypeBadge'])
                ->make(true);
        }
    }

    /**
     * Display progress notes for a specific medical case.
     *
     * @param Request $request
     * @param int $case_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function caseIndex(Request $request, $case_id)
    {
        if ($request->ajax()) {
            $data = DB::table('progress_notes')
                ->leftJoin('appointments', 'appointments.id', 'progress_notes.appointment_id')
                ->leftJoin('users', 'users.id', 'progress_notes._who_added')
                ->whereNull('progress_notes.deleted_at')
                ->where('progress_notes.medical_case_id', $case_id)
                ->orderBy('progress_notes.note_date', 'desc')
                ->select(
                    'progress_notes.*',
                    'appointments.appointment_no',
                    DB::raw("CONCAT(users.surname, ' ', users.othername) as added_by")
                )
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewProgressNote(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editProgressNote(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteProgressNote(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('noteTypeBadge', function ($row) {
                    $class = 'default';
                    if ($row->note_type == 'SOAP') $class = 'primary';
                    elseif ($row->note_type == 'General') $class = 'info';
                    elseif ($row->note_type == 'Follow-up') $class = 'warning';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.note_type_' . strtolower(str_replace('-', '_', $row->note_type))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'noteTypeBadge'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'note_date' => 'required|date',
            'patient_id' => 'required|exists:patients,id',
        ], [
            'note_date.required' => __('validation.custom.note_date.required'),
            'patient_id.required' => __('validation.custom.patient_id.required'),
        ])->validate();

        $status = ProgressNote::create([
            'subjective' => $request->subjective,
            'objective' => $request->objective,
            'assessment' => $request->assessment,
            'plan' => $request->plan,
            'note_date' => $request->note_date,
            'note_type' => $request->note_type ?? 'SOAP',
            'appointment_id' => $request->appointment_id,
            'medical_case_id' => $request->medical_case_id,
            'patient_id' => $request->patient_id,
            '_who_added' => Auth::User()->id
        ]);

        if ($status) {
            return response()->json(['message' => __('medical_cases.progress_note_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $note = ProgressNote::with(['patient', 'medicalCase', 'appointment', 'addedBy'])->findOrFail($id);
        return response()->json($note);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $note = ProgressNote::where('id', $id)->first();
        return response()->json($note);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'note_date' => 'required|date',
        ], [
            'note_date.required' => __('validation.custom.note_date.required'),
        ])->validate();

        $status = ProgressNote::where('id', $id)->update([
            'subjective' => $request->subjective,
            'objective' => $request->objective,
            'assessment' => $request->assessment,
            'plan' => $request->plan,
            'note_date' => $request->note_date,
            'note_type' => $request->note_type,
        ]);

        if ($status) {
            return response()->json(['message' => __('medical_cases.progress_note_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = ProgressNote::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('medical_cases.progress_note_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
