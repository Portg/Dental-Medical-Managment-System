<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

use App\Exports\ExpenseExport;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    private ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
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
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->expenseService->getExpenseList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('purchase_no', function ($row) {
                    return '<a href="' . url('expenses/' . $row->id) . '">' . $row->purchase_no . '</a>';
                })
                ->addColumn('amount', function ($row) {
                    return number_format($this->expenseService->totalAmount($row->id));
                })
                ->addColumn('paid_amount', function ($row) {
                    $paid_amount = $this->expenseService->totalAmount($row->id) - $this->expenseService->purchaseBalance($row->id);
                    return number_format($paid_amount);
                })
                ->addColumn('due_amount', function ($row) {
                    if ($this->expenseService->purchaseBalance($row->id) <= 0) {
                        return '<span class="text-primary">' . number_format($this->expenseService->purchaseBalance($row->id)) . '</span>';
                    }
                    return number_format($this->expenseService->purchaseBalance($row->id)) . '<br>
                    <a href="#" onclick="RecordPayment(' . $row->id . ')" class="text-primary">' . __('expenses.record_payment') . '</a> ';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->othername;
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> Action
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="' . url('expenses/' . $row->id) . '"> ' . __('expenses.view_purchase') . '</a>
                            </li>
                             <li>
                                <a  href="#"  onclick="deleteRecord(' . $row->id . ')" > ' . __('common.delete') . '  </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['purchase_no', 'status', 'action', 'due_amount'])
                ->make(true);
        }

        $data['chart_of_accts'] = $this->expenseService->getChartOfAccounts();
        $data['payment_accts'] = $this->expenseService->getPaymentAccounts();

        return view('expenses.index')->with($data);
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
            'purchase_date' => 'required',
            'supplier' => 'required'
        ], [
            'purchase_date.required' => __('validation.attributes.purchase_date') . ' ' . __('validation.required'),
            'supplier.required' => __('validation.attributes.supplier_name') . ' ' . __('validation.required'),
        ])->validate();

        $expense = $this->expenseService->createExpense(
            $request->only(['supplier', 'purchase_date']),
            $request->addmore
        );

        if ($expense) {
            return response()->json(['message' => __('expenses.added_successfully'), 'status' => true]);
        }

        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $expense_id
     * @return \Illuminate\Http\Response
     */
    public function show($expense_id)
    {
        $data['purchase_details'] = $this->expenseService->getExpenseDetail($expense_id);
        $data['expense_id'] = $expense_id;
        $data['payment_accts'] = $this->expenseService->getPaymentAccounts();

        return view('expense_items.index')->with($data);
    }

    public function exportReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->expenseService->getExportData($from, $to);

        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::download(new ExpenseExport($data, $sheet_title), 'expenses-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Expense $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(\App\Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Expense $expense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, \App\Expense $expense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->expenseService->deleteExpense($id);
        if ($status) {
            return response()->json(['message' => __('expenses.deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
