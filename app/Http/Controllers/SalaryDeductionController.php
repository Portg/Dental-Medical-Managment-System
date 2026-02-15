<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\SalaryDeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SalaryDeductionController extends Controller
{
    private SalaryDeductionService $service;

    public function __construct(SalaryDeductionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $pay_slip_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $pay_slip_id)
    {
        if ($request->ajax()) {
            $data = $this->service->getDeductionsForPaySlip($pay_slip_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->deduction_amount);
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editDeductionRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteDeductionRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['total_amount', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('payslips.show.index');
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
            'deduction' => 'required',
            'amount' => 'required'
        ], [
            'deduction.required' => __('validation.custom.deduction.required'),
            'amount.required' => __('validation.custom.amount.required')
        ])->validate();

        $success = $this->service->createDeduction($request->all(), Auth::User()->id);
        return FunctionsHelper::messageResponse(__('messages.salary_deduction_added_successfully'), $success);
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
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->service->getDeductionForEdit($id));
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
            'deduction' => 'required',
            'amount' => 'required'
        ], [
            'deduction.required' => __('validation.custom.deduction.required'),
            'amount.required' => __('validation.custom.amount.required')
        ])->validate();

        $success = $this->service->updateDeduction($id, $request->all(), Auth::User()->id);
        return FunctionsHelper::messageResponse(__('messages.salary_deduction_updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $success = $this->service->deleteDeduction($id);
        return FunctionsHelper::messageResponse(__('messages.salary_deduction_deleted_successfully'), $success);
    }
}
