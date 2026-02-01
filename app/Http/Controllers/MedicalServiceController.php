<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\MedicalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('medical_services')
                ->leftJoin('users', 'users.id', 'medical_services._who_added')
                ->whereNull('medical_services.deleted_at')
                ->select(['medical_services.*', 'users.surname']);

            if ($request->has('search') && $request->search) {
                $query->where('medical_services.name', 'like', '%' . $request->search . '%');
            }

            $data = $query->orderBy('medical_services.id', 'desc')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('price', function ($row) {
                    return number_format($row->price);
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                            ' . __('common.action') . '
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')">' . __('common.edit') . '</a>
                            </li>
                            <li>
                                <a href="#" onclick="deleteRecord(' . $row->id . ')">' . __('common.delete') . '</a>
                            </li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('clinical_services.index');
    }

    public function servicesArray(Request $request)
    {

        $result = MedicalService::select('name')->get();
        $data = [];
        foreach ($result as $row) {
            $data[] = $row->name;
        }
        echo json_encode($data);
    }


    public function filterServices(Request $request)
    {
        $data = [];
        $name = $request->q;

        if ($name) {
            $search = $name;
            $data = MedicalService::where('name', 'LIKE', "%$search%")->get();

            $formatted_tags = [];
            foreach ($data as $tag) {
                $formatted_tags[] = ['id' => $tag->id, 'text' => $tag->name, 'price' => $tag->price];
            }
            return \Response::json($formatted_tags);
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
            'name' => 'required',
            'price' => 'required'
        ])->validate();
        $status = MedicalService::create([
            'name' => $request->name,
            'price' => $request->price,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\MedicalService $medicalService
     * @return \Illuminate\Http\Response
     */
    public function show(MedicalService $medicalService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\MedicalService $medicalService
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $service = MedicalService::where('id', $id)->first();
        return response()->json($service);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required'
        ])->validate();
        $status = MedicalService::where('id', $id)->update([
            'name' => $request->name,
            'price' => $request->price,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\MedicalService $medicalService
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $status = MedicalService::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }

}
