<?php

namespace App\Http\Controllers;

use App\Services\AllergyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AllergyController extends Controller
{
    private AllergyService $allergyService;

    public function __construct(AllergyService $allergyService)
    {
        $this->allergyService = $allergyService;
        $this->middleware('can:edit-patients');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {

            $data = $this->allergyService->getListByPatient($patient_id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == "Active") {
                        $btn = '<span class="label label-sm label-danger"> ' . __('common.active') . ' </span>';
                    } else {
                        $btn = '<span class="label label-sm label-success"> ' . __('common.inactive') . ' </span>';
                    }
                    return $btn;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editAllergy(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteAllergy(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'body_reaction' => 'required'
        ], [
            'body_reaction.required' => __('validation.custom.body_reaction.required')
        ])->validate();

        $status = $this->allergyService->create($request->body_reaction, $request->patient_id);

        if ($status) {
            return response()->json(['message' => __('medical_history.allergy_captured_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Allergy $allergy
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->allergyService->find($id));
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
            'body_reaction' => 'required'
        ], [
            'body_reaction.required' => __('validation.custom.body_reaction.required')
        ])->validate();

        $status = $this->allergyService->update($id, $request->body_reaction);

        if ($status) {
            return response()->json(['message' => __('medical_history.allergy_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->allergyService->delete($id);

        if ($status) {
            return response()->json(['message' => __('medical_history.allergy_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
