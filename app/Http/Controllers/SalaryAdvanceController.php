<?php

namespace App\Http\Controllers;

use App\Services\SalaryAdvanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SalaryAdvanceController extends Controller
{
    private SalaryAdvanceService $service;

    public function __construct(SalaryAdvanceService $service)
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
            $data = $this->service->getList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('employee', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->LoggedInUser;
                })
                ->addColumn('amount', function ($row) {
                    return '<span class="text-primary">' . number_format($row->advance_amount) . '</span>';
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['amount','editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('salary_advances.index');
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'payment_classification' => 'required',
            'employee' => 'required',
            'advance_month' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
            'payment_date' => 'required'
        ])->validate();

        $status = $this->service->create($request->only([
            'payment_classification', 'employee', 'advance_month', 'amount',
            'payment_method', 'payment_date',
        ]));

        if ($status) {
            return response()->json(['message' => __('salary_advances.payment_captured_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\SalaryAdvance $salaryAdvance
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
        return response()->json($this->service->find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'payment_classification' => 'required',
            'employee' => 'required',
            'advance_month' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
            'payment_date' => 'required'
        ])->validate();

        $status = $this->service->update($id, $request->only([
            'payment_classification', 'employee', 'advance_month', 'amount',
            'payment_method', 'payment_date',
        ]));

        if ($status) {
            return response()->json(['message' => __('salary_advances.payment_updated_successfully'), 'status' => true]);
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
        $status = $this->service->delete($id);

        if ($status) {
            return response()->json(['message' => __('salary_advances.advance_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
