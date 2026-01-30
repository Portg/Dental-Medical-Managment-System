<?php

namespace App\Http\Controllers;

use App\ExpenseItem;
use App\ExpensePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ExpensePaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $expense_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $expense_id)
    {
        if ($request->ajax()) {

            $data = ExpensePayment::where('expense_id', $expense_id)->OrderBy('updated_at', 'DESC')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('payment_acct', function ($row) {
                    return $row->paymentAccount->name;
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('added_by', function ($row) {
                    return $row->AddedBy->othername;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editPaymentRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {

                    $btn = '<a href="#" onclick="deletePaymentRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('expense_items.index', compact('expense_id'));
    }

    //show modal for updating the payment balance

    public function supplier_balance($purchase_id)
    {
        $invoice_amount = ExpenseItem::where('expense_id', $purchase_id)->sum(DB::raw('qty * price'));

        $amount_paid = ExpensePayment::where('expense_id', $purchase_id)->sum('amount');
        //remaining balance
        $balance = $invoice_amount - $amount_paid;
        $data['amount'] = $balance;
        $data['today_date'] = date('Y-m-d');
        return response()->json($data);
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
            'payment_date' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
            'payment_account' => 'required'
        ], [
            'payment_date.required' => __('validation.attributes.payment_date') . ' ' . __('validation.required'),
            'amount.required' => __('validation.attributes.amount') . ' ' . __('validation.required'),
            'payment_method.required' => __('validation.attributes.payment_method') . ' ' . __('validation.required'),
            'payment_account.required' => __('validation.attributes.payment_account') . ' ' . __('validation.required'),
        ])->validate();

        $status = ExpensePayment::create([
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_account_id' => $request->payment_account,
            'expense_id' => $request->expense_id,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('expense_items.payments.payment_captured_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\ExpensePayment $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function show(ExpensePayment $expensePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\ExpensePayment $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $payment = ExpensePayment::where('id', $id)->first();
        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\ExpensePayment $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'payment_date' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
            'payment_account' => 'required'
        ], [
            'payment_date.required' => __('validation.attributes.payment_date') . ' ' . __('validation.required'),
            'amount.required' => __('validation.attributes.amount') . ' ' . __('validation.required'),
            'payment_method.required' => __('validation.attributes.payment_method') . ' ' . __('validation.required'),
            'payment_account.required' => __('validation.attributes.payment_account') . ' ' . __('validation.required'),
        ])->validate();

        $status = ExpensePayment::where('id', $id)->update([
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_account_id' => $request->payment_account,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('expense_items.payments.payment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = ExpensePayment::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('expense_items.payments.payment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }
}
