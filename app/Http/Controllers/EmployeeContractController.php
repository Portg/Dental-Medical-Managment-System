<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Services\EmployeeContractService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class EmployeeContractController extends Controller
{
    private EmployeeContractService $employeeContractService;

    public function __construct(EmployeeContractService $employeeContractService)
    {
        $this->employeeContractService = $employeeContractService;
        $this->middleware('can:manage-employees');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->employeeContractService->getContractList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->loggedInName;
                })
                ->addColumn('employee', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('contract_validity', function ($row) {
                    return $row->contract_length . " " . $row->contract_period;
                })
                ->addColumn('contract_end_date', function ($row) {
                    return $this->employeeContractService->calculateContractEndDate(
                        $row->contract_period,
                        $row->start_date,
                        $row->contract_length
                    );
                })
                ->addColumn('amount', function ($row) {
                    if ($row->payroll_type == "Salary") {
                        return '<span class="text-primary">' . number_format($row->gross_salary) . '<br></span>' . __('employee_contracts.salary');
                    } else if ($row->payroll_type == "Commission") {
                        return '<span class="text-primary">' . number_format($row->commission_percentage) . '%<br></span>' . __('employee_contracts.commission');
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '';
                    if ($row->status == 'Active') {
                        $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')"> ' . __('common.edit') . ' </a>
                            </li>
                              <li>
                                <a href="#" onclick="deleteRecord(' . $row->id . ')"> ' . __('common.delete') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    }
                    return $btn;
                })
                ->rawColumns(['amount', 'action'])
                ->make(true);
        }
        return view('employee_contracts.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'employee' => 'required',
            'contract_type' => 'required',
            'start_date' => 'required',
            'contract_length' => 'required',
            'contract_period' => 'required',
            'payroll_type' => 'required'
        ], [
            'employee.required' => __('validation.attributes.employee') . ' ' . __('validation.required'),
            'contract_type.required' => __('validation.attributes.contract_type') . ' ' . __('validation.required'),
            'start_date.required' => __('validation.attributes.start_date') . ' ' . __('validation.required'),
            'contract_length.required' => __('validation.attributes.contract_length') . ' ' . __('validation.required'),
            'contract_period.required' => __('validation.attributes.contract_period') . ' ' . __('validation.required'),
            'payroll_type.required' => __('validation.attributes.payroll_type') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->employeeContractService->createContract($request->only([
            'employee', 'contract_type', 'start_date', 'contract_length',
            'contract_period', 'payroll_type', 'gross_salary', 'commission_percentage',
        ]), Auth::User()->id);

        if ($status) {
            return response()->json(['message' => __('employee_contracts.employee_contract_added_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'),
            'status' => false]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\EmployeeContract $employeeContract
     * @return Response
     */
    public function edit($id)
    {
        return response()->json($this->employeeContractService->getContractForEdit((int) $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'employee' => 'required',
            'contract_type' => 'required',
            'start_date' => 'required',
            'contract_length' => 'required',
            'contract_period' => 'required',
            'payroll_type' => 'required'
        ], [
            'employee.required' => __('validation.attributes.employee') . ' ' . __('validation.required'),
            'contract_type.required' => __('validation.attributes.contract_type') . ' ' . __('validation.required'),
            'start_date.required' => __('validation.attributes.start_date') . ' ' . __('validation.required'),
            'contract_length.required' => __('validation.attributes.contract_length') . ' ' . __('validation.required'),
            'contract_period.required' => __('validation.attributes.contract_period') . ' ' . __('validation.required'),
            'payroll_type.required' => __('validation.attributes.payroll_type') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->employeeContractService->updateContract((int) $id, $request->only([
            'employee', 'contract_type', 'start_date', 'contract_length',
            'contract_period', 'payroll_type', 'gross_salary', 'commission_percentage',
        ]), Auth::User()->id);

        if ($status) {
            return response()->json(['message' => __('employee_contracts.employee_contract_updated_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'),
            'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Response
     */
    public function destroy($id)
    {
        $status = $this->employeeContractService->deleteContract((int) $id);
        if ($status) {
            return response()->json(['message' => __('employee_contracts.employee_contract_deleted_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'),
            'status' => false]);
    }
}
