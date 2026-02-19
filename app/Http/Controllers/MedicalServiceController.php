<?php

namespace App\Http\Controllers;

use App\Services\MedicalServiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalServiceController extends Controller
{
    private MedicalServiceService $medicalServiceService;

    public function __construct(MedicalServiceService $medicalServiceService)
    {
        $this->medicalServiceService = $medicalServiceService;
        $this->middleware('can:manage-medical-services');
    }

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
            $data = $this->medicalServiceService->getServiceList($request->search);

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
        $data = $this->medicalServiceService->getAllServiceNames();
        echo json_encode($data);
    }

    public function filterServices(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->medicalServiceService->filterServices($name));
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

        $status = $this->medicalServiceService->createService($request->only(['name', 'price']));
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_added_successfully'), 'status' => true]);
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
        return response()->json($this->medicalServiceService->getServiceForEdit($id));
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

        $status = $this->medicalServiceService->updateService($id, $request->only(['name', 'price']));
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_updated_successfully'), 'status' => true]);
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
        $status = $this->medicalServiceService->deleteService($id);
        if ($status) {
            return response()->json(['message' => __('clinical_services.clinical_services_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
