<?php

namespace App\Http\Controllers;

use App\Services\InvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoicePaymentController extends Controller
{
    private InvoicePaymentService $invoicePaymentService;

    public function __construct(InvoicePaymentService $invoicePaymentService)
    {
        $this->invoicePaymentService = $invoicePaymentService;
    }

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

            $data = $this->invoicePaymentService->getPaymentsByInvoice($invoice_id);
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

        $status = $this->invoicePaymentService->createPayment($request->all());
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
    public function show(\App\InvoicePayment $invoicePayment)
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
        return response()->json($this->invoicePaymentService->getPaymentForEdit($id));
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
            'amount' => 'required',
            'payment_date' => 'required',
            'payment_method' => 'required'
        ])->validate();

        $status = $this->invoicePaymentService->updatePayment($id, $request->all());
        if ($status) {
            return response()->json(['message' => __('messages.payment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->invoicePaymentService->deletePayment($id);
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

        $result = $this->invoicePaymentService->processMixedPayment(
            $request->invoice_id,
            $request->payments,
            $request->payment_date
        );

        return response()->json($result);
    }

    /**
     * 获取支付方式列表
     */
    public function getPaymentMethods()
    {
        return response()->json($this->invoicePaymentService->getPaymentMethodsList());
    }

    /**
     * 计算找零金额
     */
    public function calculateChange(Request $request)
    {
        $receivedAmount = floatval($request->received_amount ?? 0);
        return response()->json(
            $this->invoicePaymentService->calculateChange($request->invoice_id, $receivedAmount)
        );
    }
}
