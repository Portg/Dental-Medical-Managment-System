<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\PaySlipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PaySlipController extends Controller
{
    private PaySlipService $service;

    public function __construct(PaySlipService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-payroll');
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

            $data = $this->service->getPaySlipList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return '';
                })
                ->addColumn('employee', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('basic_salary', function ($row) {
                    $wage = $this->service->calculateWage($row);
                    return '<span class="text-primary">' . number_format($wage) . '</span>';
                })
                ->addColumn('total_advances', function ($row) {
                    $advances = $this->service->employeeAdvances($row);
                    return '<span class="text-primary">' . number_format($advances) . '</span>';
                })
                ->addColumn('total_allowances', function ($row) {
                    $allowances = $this->service->employeeAllowances($row);
                    return '<span class="text-primary">' . number_format($allowances) . '</span>';
                })
                ->addColumn('total_deductions', function ($row) {
                    $deductions = $this->service->employeeDeductions($row);
                    return '<span class="text-primary">' . number_format($deductions) . '</span>';
                })
                ->addColumn('due_balance', function ($row) {
                    $deductions = $this->service->employeeDeductions($row);
                    $allowance = $this->service->employeeAllowances($row);
                    $advances = $this->service->employeeAdvances($row);
                    $wage = $this->service->calculateWage($row);
                    $balance = ($allowance + $wage) - ($deductions + $advances);
                    return '<span class="text-primary">' . number_format($balance) . '</span>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="' . url('payslips/' . $row->id) . '"> ' . __('common.preview') . ' </a>
                            </li>
                              <li>
                                <a href="#" onclick="deleteRecord(' . $row->id . ')"> ' . __('common.delete') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['basic_salary', 'total_advances', 'total_allowances', 'total_deductions', 'due_balance', 'action'])
                ->make(true);
        }
        return view('payslips.index');
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
            'employee' => 'required',
            'payslip_month' => 'required'
        ])->validate();

        $result = $this->service->createPaySlip(
            (int) $request->employee,
            $request->payslip_month,
            $request->addAllowance ?? [],
            $request->addDeduction ?? []
        );

        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $pay_slip_id
     * @return \Illuminate\Http\Response
     */
    public function show($pay_slip_id)
    {
        $data['employee'] = $this->service->getPaySlipDetail((int) $pay_slip_id);
        $data['pay_slip_id'] = $pay_slip_id;
        return view('payslips.show.index')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\PaySlip $paySlip
     * @return \Illuminate\Http\Response
     */
    public function edit(\App\PaySlip $paySlip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\PaySlip $paySlip
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, \App\PaySlip $paySlip)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $success = $this->service->deletePaySlip((int) $id);
        return FunctionsHelper::messageResponse(__('payslips.payslip_deleted_successfully'), $success);
    }

    public function individualPaySlip(Request $request)
    {
        $data['employee'] = $this->service->getPaySlipDetail((int) $request->pay_slip_id);
        $data['pay_slip_id'] = $request->pay_slip_id;

        return view('payslips.individual_payslips');
    }
}
