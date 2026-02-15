<?php

namespace App\Http\Controllers;

use App\Services\ChronicDiseaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ChronicDiseasesController extends Controller
{
    private ChronicDiseaseService $chronicDiseaseService;

    public function __construct(ChronicDiseaseService $chronicDiseaseService)
    {
        $this->chronicDiseaseService = $chronicDiseaseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {

            $data = $this->chronicDiseaseService->getListByPatient($patient_id);
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
                    $btn = '<a href="#" onclick="editIllness(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteIllness(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
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
            'disease' => 'required',
            'status' => 'required'
        ], [
            'disease.required' => __('validation.custom.disease.required'),
            'status.required' => __('validation.custom.status.required')
        ])->validate();

        $status = $this->chronicDiseaseService->create($request->disease, $request->status, $request->patient_id);

        if ($status) {
            return response()->json(['message' => __('messages.chronic_disease_added_successfully'), 'status' => true]);
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
        return response()->json($this->chronicDiseaseService->find($id));
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
            'disease' => 'required',
            'status' => 'required'
        ], [
            'disease.required' => __('validation.custom.disease.required'),
            'status.required' => __('validation.custom.status.required')
        ])->validate();

        $status = $this->chronicDiseaseService->update($id, $request->disease, $request->status);

        if ($status) {
            return response()->json(['message' => __('messages.chronic_disease_updated_successfully'), 'status' => true]);
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
        $status = $this->chronicDiseaseService->delete($id);

        if ($status) {
            return response()->json(['message' => __('messages.chronic_disease_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
