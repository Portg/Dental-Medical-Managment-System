<?php

namespace App\Http\Controllers;

use App\Services\InsuranceCompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InsuranceCompaniesController extends Controller
{
    private InsuranceCompanyService $insuranceCompanyService;

    public function __construct(InsuranceCompanyService $insuranceCompanyService)
    {
        $this->insuranceCompanyService = $insuranceCompanyService;
        $this->middleware('can:manage-insurance');
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

            $data = $this->insuranceCompanyService->getCompanyList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->addColumn('status', function ($row) {
                    if ($row->deleted_at != null) {
                        return '<span class="text-danger">' . __('common.inactive') . '</span>';
                    } else {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
                    }
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->deleted_at == null) {
                        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    }
                })
                ->addColumn('deleteBtn', function ($row) {

                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';

                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('insurance_companies.index');
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
            'name' => 'required'
        ], [
            'name.required' => __('validation.attributes.insurance_company_name') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->insuranceCompanyService->createCompany($request->only(['name', 'phone_no', 'email']));
        if ($status) {
            return response()->json(['message' => __('insurance_companies.added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
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
        return response()->json($this->insuranceCompanyService->getCompanyForEdit((int) $id));
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
            'name' => 'required'
        ], [
            'name.required' => __('validation.attributes.insurance_company_name') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->insuranceCompanyService->updateCompany((int) $id, $request->only(['name', 'phone_no', 'email']));
        if ($status) {
            return response()->json(['message' => __('insurance_companies.updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    public function filterCompanies(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->insuranceCompanyService->filterCompanies($name));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->insuranceCompanyService->deleteCompany((int) $id);
        if ($status) {
            return response()->json(['message' => __('insurance_companies.deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
