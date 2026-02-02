<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\MemberTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoicePaymentController extends Controller
{
    // 支持的支付方式 (PRD 4.1.3)
    const PAYMENT_METHODS = [
        'Cash' => ['label' => 'invoices.cash', 'fee' => 0],
        'WeChat' => ['label' => 'invoices.wechat_pay', 'fee' => 0.006],
        'Alipay' => ['label' => 'invoices.alipay', 'fee' => 0.006],
        'BankCard' => ['label' => 'invoices.bank_card', 'fee' => 0.005],
        'StoredValue' => ['label' => 'invoices.stored_value', 'fee' => 0],
        'Insurance' => ['label' => 'invoices.insurance', 'fee' => 0],
        'Online Wallet' => ['label' => 'invoices.online_wallet', 'fee' => 0],
        'Mobile Money' => ['label' => 'invoices.mobile_money', 'fee' => 0],
        'Cheque' => ['label' => 'invoices.cheque', 'fee' => 0],
        'Self Account' => ['label' => 'invoices.self_account', 'fee' => 0],
        'Credit' => ['label' => 'invoices.credit', 'fee' => 0],
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $invoice_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $invoice_id)
    {
        if ($request->ajax()) {

            $data = InvoicePayment::where('invoice_id', $invoice_id)->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('added_by', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->addedBy->surname, $row->addedBy->othername);
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="edit_Payment(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="delete_payment(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
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
            'amount' => 'required',
            'payment_date' => 'required',
            'payment_method' => 'required',
            'invoice_id' => 'required'
        ])->validate();

        $status = InvoicePayment::create([
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'cheque_no' => $request->cheque_no,
            'account_name' => $request->account_name,
            'bank_name' => $request->bank_name,
            'invoice_id' => $request->invoice_id,
            'insurance_company_id' => $request->insurance_company_id,
            'self_account_id' => $request->self_account_id,
            'branch_id' => Auth::User()->branch_id,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('messages.payment_recorded_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\InvoicePayment $invoicePayment
     * @return \Illuminate\Http\Response
     */
    public function show(InvoicePayment $invoicePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\InvoicePayment $invoicePayment
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $payment = DB::table('invoice_payments')
            ->leftJoin('insurance_companies', 'insurance_companies.id',
                'invoice_payments.insurance_company_id')
            ->where('invoice_payments.id', $id)
            ->select('invoice_payments.*', 'insurance_companies.name')
            ->first();
        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\InvoicePayment $invoicePayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        Validator::make($request->all(), [
            'amount' => 'required',
            'payment_date' => 'required',
            'payment_method' => 'required'
        ])->validate();

        $status = InvoicePayment::where('id', $id)->update([
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'cheque_no' => $request->cheque_no,
            'account_name' => $request->account_name,
            'bank_name' => $request->bank_name,
            'insurance_company_id' => $request->insurance_company_id,
            'self_account_id' => $request->self_account_id,
            'branch_id' => Auth::User()->branch_id,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('messages.payment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\InvoicePayment $invoicePayment
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = InvoicePayment::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('messages.payment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * 混合支付 - 支持多种支付方式组合
     * PRD 4.1.3: 混合支付
     */
    public function storeMixed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        $invoice = Invoice::findOrFail($request->invoice_id);

        // 检查发票是否可以收款
        if (!$invoice->canAcceptPayment()) {
            return response()->json([
                'message' => __('invoices.discount_approval_required'),
                'status' => false
            ]);
        }

        // 计算总支付金额
        $totalPayment = collect($request->payments)->sum('amount');
        $outstanding = $invoice->outstanding_amount;

        // 验证支付金额不超过应付
        if ($totalPayment > $outstanding) {
            return response()->json([
                'message' => __('invoices.payment_exceeds_outstanding'),
                'status' => false
            ]);
        }

        DB::beginTransaction();
        try {
            $paymentDate = $request->payment_date ?? now()->format('Y-m-d');
            $patient = $invoice->patient;

            foreach ($request->payments as $paymentData) {
                $method = $paymentData['payment_method'];
                $amount = $paymentData['amount'];

                if ($amount <= 0) continue;

                // 储值卡支付特殊处理
                if ($method === 'StoredValue') {
                    if (!$patient) {
                        throw new \Exception(__('invoices.patient_required_for_stored_value'));
                    }

                    $storedBalance = $patient->stored_balance ?? 0;
                    if ($amount > $storedBalance) {
                        throw new \Exception(__('invoices.insufficient_stored_balance'));
                    }

                    // 扣减储值余额
                    $patient->stored_balance = $storedBalance - $amount;
                    $patient->save();

                    // 记录会员交易
                    if (class_exists('\App\MemberTransaction')) {
                        MemberTransaction::create([
                            'patient_id' => $patient->id,
                            'type' => 'payment',
                            'amount' => -$amount,
                            'balance_after' => $patient->stored_balance,
                            'description' => __('invoices.stored_value_payment', ['invoice_no' => $invoice->invoice_no]),
                            '_who_added' => Auth::id(),
                        ]);
                    }
                }

                // 创建支付记录
                InvoicePayment::create([
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $method,
                    'cheque_no' => $paymentData['cheque_no'] ?? null,
                    'account_name' => $paymentData['account_name'] ?? null,
                    'bank_name' => $paymentData['bank_name'] ?? null,
                    'invoice_id' => $invoice->id,
                    'insurance_company_id' => $paymentData['insurance_company_id'] ?? null,
                    'self_account_id' => $paymentData['self_account_id'] ?? null,
                    'transaction_ref' => $paymentData['transaction_ref'] ?? null,
                    'branch_id' => Auth::user()->branch_id ?? null,
                    '_who_added' => Auth::id(),
                ]);
            }

            // 更新发票已付金额
            $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $totalPayment;
            $invoice->save();

            // 更新会员积分 (BR-036)
            if ($patient && $patient->memberLevel && $patient->memberLevel->points_rate > 0) {
                $points = floor($totalPayment * $patient->memberLevel->points_rate);
                $patient->points = ($patient->points ?? 0) + $points;
                $patient->total_consumption = ($patient->total_consumption ?? 0) + $totalPayment;
                $patient->save();
            }

            DB::commit();

            // 计算找零 (如果是现金支付)
            $change = 0;
            if ($totalPayment > $outstanding) {
                $change = $totalPayment - $outstanding;
            }

            return response()->json([
                'message' => __('invoices.payment_recorded_successfully'),
                'status' => true,
                'paid_amount' => $totalPayment,
                'change_due' => $change,
                'new_balance' => $invoice->outstanding_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ]);
        }
    }

    /**
     * 获取支付方式列表
     */
    public function getPaymentMethods()
    {
        $methods = [];
        foreach (self::PAYMENT_METHODS as $key => $value) {
            $methods[] = [
                'value' => $key,
                'label' => __($value['label']),
                'fee' => $value['fee'],
            ];
        }
        return response()->json($methods);
    }

    /**
     * 计算找零金额
     */
    public function calculateChange(Request $request)
    {
        $invoice = Invoice::findOrFail($request->invoice_id);
        $receivedAmount = floatval($request->received_amount ?? 0);
        $outstanding = $invoice->outstanding_amount;

        $change = max(0, $receivedAmount - $outstanding);

        return response()->json([
            'outstanding' => $outstanding,
            'received' => $receivedAmount,
            'change_due' => $change,
        ]);
    }
}
