<?php

namespace App\Http\Controllers;

use App\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SurgeriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {

            $data = Surgery::where('patient_id', $patient_id)->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('added_by', function ($row) {
                    return $row->addedBy->othername;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editSurgery(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteSurgery(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
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
            'patient_id' => 'required',
            'surgery' => 'required',
            'surgery_date' => 'required'
        ], [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'surgery.required' => __('validation.custom.surgery.required'),
            'surgery_date.required' => __('validation.custom.surgery_date.required')
        ])->validate();
        $status = Surgery::create([
            'surgery' => $request->surgery,
            'surgery_date' => $request->surgery_date,
            'description' => $request->description,
            'patient_id' => $request->patient_id,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('medical_history.surgery_added_successfully'), 'status' => true]);
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
        $surgery = Surgery::where('id', $id)->first();
        return response()->json($surgery);
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
            'surgery' => 'required',
            'surgery_date' => 'required'
        ], [
            'surgery.required' => __('validation.custom.surgery.required'),
            'surgery_date.required' => __('validation.custom.surgery_date.required')
        ])->validate();
        $status = Surgery::where('id', $id)->update([
            'surgery' => $request->surgery,
            'surgery_date' => $request->surgery_date,
            'description' => $request->description,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('medical_history.surgery_updated_successfully'), 'status' => true]);
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
        $status = Surgery::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('medical_history.surgery_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
