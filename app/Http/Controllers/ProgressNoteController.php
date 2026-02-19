<?php

namespace App\Http\Controllers;

use App\Services\ProgressNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ProgressNoteController extends Controller
{
    private ProgressNoteService $progressNoteService;

    public function __construct(ProgressNoteService $progressNoteService)
    {
        $this->progressNoteService = $progressNoteService;
        $this->middleware('can:edit-patients');
    }

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
            $data = $this->progressNoteService->getNotesByPatient($patient_id);

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
            $data = $this->progressNoteService->getNotesByCase($case_id);

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

        $status = $this->progressNoteService->createNote($request->only([
            'subjective', 'objective', 'assessment', 'plan',
            'note_date', 'note_type', 'appointment_id', 'medical_case_id', 'patient_id',
        ]));

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
        return response()->json($this->progressNoteService->getNoteWithRelations($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->progressNoteService->getNoteForEdit($id));
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

        $status = $this->progressNoteService->updateNote($id, $request->only([
            'subjective', 'objective', 'assessment', 'plan', 'note_date', 'note_type',
        ]));

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
        $status = $this->progressNoteService->deleteNote($id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.progress_note_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
