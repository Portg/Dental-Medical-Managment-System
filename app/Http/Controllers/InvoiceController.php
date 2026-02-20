<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\InvoiceService;
use App\Exports\InvoiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PDF;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;

        $this->middleware('can:view-invoices')->only(['index', 'show', 'previewInvoice', 'invoiceShareDetails', 'sendInvoice', 'invoiceAmount', 'patientInvoices', 'printReceipt', 'exportReport', 'invoiceProceduresToJson', 'searchInvoices']);
        $this->middleware('can:create-invoices')->only(['create', 'store']);
        $this->middleware('can:edit-invoices')->only(['edit', 'update', 'pendingDiscountApprovals', 'approveDiscount', 'rejectDiscount', 'setCredit']);
        $this->middleware('can:delete-invoices')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->invoiceService->getInvoiceList([
                'search'     => $request->input('search.value', ''),
                'status'     => $request->input('status'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
                'page'       => $request->input('page'),
                'per_page'   => $request->input('per_page'),
            ]);

            return $this->invoiceService->buildIndexDataTable($data);
        }
        return view('invoices.index');
    }

    public function previewInvoice($invoice_id)
    {
        $data = $this->invoiceService->getPreviewData((int) $invoice_id);
        return view('invoices.preview', $data);
    }

    public function invoiceShareDetails(Request $request, $invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceShareDetails((int) $invoice_id));
    }

    public function sendInvoice(Request $request)
    {
        Validator::make($request->all(), [
            'invoice_id' => 'required',
            'email' => 'required',
        ], [
            'invoice_id.required' => __('validation.attributes.invoice_id') . ' ' . __('validation.required'),
            'email.required' => __('validation.attributes.email') . ' ' . __('validation.required'),
        ])->validate();

        $this->invoiceService->sendInvoiceEmail((int) $request->invoice_id, $request->email, $request->message);

        return response()->json(['message' => __('emails.invoice_sent_successfully'), 'status' => true]);
    }

    public function invoiceAmount($invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceAmountData((int) $invoice_id));
    }

    /**
     * Patient-specific invoices for patient detail page.
     */
    public function patientInvoices(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPatientInvoices((int) $patient_id);

            return $this->invoiceService->buildPatientInvoicesDataTable($data);
        }
    }

    public function printReceipt($invoice_id)
    {
        $data = $this->invoiceService->getReceiptData((int) $invoice_id);

        $pdf = PDF::loadView('invoices.receipt_print', $data);
        return $pdf->stream('receipt', array("attachment" => false))->header('Content-Type', 'application/pdf');
    }

    public function exportReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->invoiceService->getExportData($from, $to);

        return Excel::download(new InvoiceExport($data), 'invoicing-report-' . date('Y-m-d') . '.xlsx');
    }

    public function invoiceProceduresToJson($InvoiceId)
    {
        return response()->json($this->invoiceService->getInvoiceProcedures((int) $InvoiceId));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $result = $this->invoiceService->createInvoice((int) $request->appointment_id, $request->addmore);
        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show($invoice)
    {
        $data = $this->invoiceService->getInvoiceDetail((int) $invoice);
        return view('invoices.show.index')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return response()->json($this->invoiceService->deleteInvoice((int) $id));
    }

    /**
     * 待折扣审批列表 (PRD 4.1.2 BR-035)
     */
    public function pendingDiscountApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPendingDiscountApprovals();

            return $this->invoiceService->buildDiscountApprovalsDataTable($data);
        }

        return view('invoices.pending_discount_approvals');
    }

    /**
     * 审批折扣 - 批准 (PRD 4.1.2 BR-035)
     */
    public function approveDiscount(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->approveDiscount((int) $id, Auth::id(), $request->reason)
        );
    }

    /**
     * 审批折扣 - 拒绝 (PRD 4.1.2 BR-035)
     */
    public function rejectDiscount(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        return response()->json(
            $this->invoiceService->rejectDiscount((int) $id, Auth::id(), $request->reason)
        );
    }

    /**
     * 设置为挂账 (PRD 4.1.3 欠费挂账)
     */
    public function setCredit(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->setCredit((int) $id, Auth::id())
        );
    }

    /**
     * 搜索发票 (用于退费页面)
     */
    public function searchInvoices(Request $request)
    {
        return response()->json(
            $this->invoiceService->searchInvoices($request->get('q', ''))
        );
    }
}
