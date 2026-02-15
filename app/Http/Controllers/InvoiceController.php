<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\InvoiceService;
use App\Exports\InvoiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PDF;
use Yajra\DataTables\DataTables;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->invoiceService->getInvoiceList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('invoice_no', function ($row) {
                    return '<a href="' . url('invoices/' . $row->id) . '">' . $row->invoice_no . '</a>';
                })
                ->addColumn('customer', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('amount', function ($row) {
                    return number_format($this->invoiceService->totalInvoiceAmount($row->id));
                })
                ->addColumn('paid_amount', function ($row) {
                    return number_format($this->invoiceService->totalInvoicePaidAmount($row->id));
                })
                ->addColumn('due_amount', function ($row) {
                    $balance = $this->invoiceService->invoiceBalance($row->id);
                    if ($balance <= 0) {
                        return number_format($balance);
                    }
                    return number_format($balance) . '<br>
                    <a href="#" onclick="record_payment(' . $row->id . ')" class="text-primary">' . __('invoices.record_payment') . '</a>
                    ';
                })
                ->addColumn('action', function ($row) {
                    return '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                        <li>
                        <a href="#" onClick="viewInvoiceProcedures('.$row->id.')"> ' . __('invoices.view_procedures_done') . '</a>
                    </li>
                    <li>
                                <a href="' . url('invoices/' . $row->id) . '"> ' . __('invoices.view_invoice_details') . '</a>
                            </li>
                             <li>
                                <a target="_blank" href="' . url('print-receipt/' . $row->id) . '"  > ' . __('invoices.print') . ' </a>
                            </li>
                              <li>
                         <a  href="#" onClick="shareInvoiceView(' . $row->id . ')"> ' . __('invoices.share_invoice') . ' </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="#" onClick="deleteInvoice(' . $row->id . ')" class="text-danger"> ' . __('invoices.delete_invoice') . '</a>
                            </li>
                        </ul>
                    </div>
                    ';
                })
                ->rawColumns(['invoice_no', 'due_amount', 'payment_classification', 'action', 'status'])
                ->make(true);
        }
        return view('invoices.index');
    }

    public function previewInvoice($invoice_id)
    {
        $data = $this->invoiceService->getPreviewData($invoice_id);
        return view('invoices.preview', $data);
    }

    public function invoiceShareDetails(Request $request, $invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceShareDetails($invoice_id));
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

        $this->invoiceService->sendInvoiceEmail($request->invoice_id, $request->email, $request->message);

        return response()->json(['message' => __('emails.invoice_sent_successfully'), 'status' => true]);
    }

    public function invoiceAmount($invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceAmountData($invoice_id));
    }

    /**
     * Patient-specific invoices for patient detail page.
     */
    public function patientInvoices(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPatientInvoices($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-';
                })
                ->addColumn('amount', function ($row) {
                    return number_format($this->invoiceService->totalInvoiceAmount($row->id));
                })
                ->addColumn('statusBadge', function ($row) {
                    $balance = $this->invoiceService->invoiceBalance($row->id);
                    $total = $this->invoiceService->totalInvoiceAmount($row->id);
                    if ($total <= 0) {
                        $class = 'default';
                        $text = '-';
                    } elseif ($balance <= 0) {
                        $class = 'success';
                        $text = __('invoices.paid');
                    } elseif ($balance < $total) {
                        $class = 'warning';
                        $text = __('invoices.partially_paid');
                    } else {
                        $class = 'danger';
                        $text = __('invoices.unpaid');
                    }
                    return '<span class="label label-' . $class . '">' . $text . '</span>';
                })
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . url('invoices/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->rawColumns(['statusBadge', 'viewBtn'])
                ->make(true);
        }
    }

    public function printReceipt($invoice_id)
    {
        $data = $this->invoiceService->getReceiptData($invoice_id);

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
        return response()->json($this->invoiceService->getInvoiceProcedures($InvoiceId));
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
        $result = $this->invoiceService->createInvoice($request->appointment_id, $request->addmore);
        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show($invoice)
    {
        $data = $this->invoiceService->getInvoiceDetail($invoice);
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
        return response()->json($this->invoiceService->deleteInvoice($id));
    }

    /**
     * 待折扣审批列表 (PRD 4.1.2 BR-035)
     */
    public function pendingDiscountApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPendingDiscountApprovals();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('invoice_no', function ($row) {
                    return '<a href="' . url('invoices/' . $row->id) . '">' . $row->invoice_no . '</a>';
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->full_name : '-';
                })
                ->addColumn('subtotal', function ($row) {
                    return number_format($row->subtotal, 2);
                })
                ->addColumn('discount_amount', function ($row) {
                    return number_format($row->discount_amount, 2);
                })
                ->addColumn('total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->addColumn('added_by', function ($row) {
                    return $row->addedBy ? $row->addedBy->othername : '-';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-success" onclick="approveDiscount(' . $row->id . ')">
                            <i class="fa fa-check"></i> ' . __('invoices.approve') . '
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectDiscount(' . $row->id . ')">
                            <i class="fa fa-times"></i> ' . __('invoices.reject') . '
                        </button>
                    ';
                })
                ->rawColumns(['invoice_no', 'action'])
                ->make(true);
        }

        return view('invoices.pending_discount_approvals');
    }

    /**
     * 审批折扣 - 批准 (PRD 4.1.2 BR-035)
     */
    public function approveDiscount(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->approveDiscount($id, Auth::id(), $request->reason)
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
            $this->invoiceService->rejectDiscount($id, Auth::id(), $request->reason)
        );
    }

    /**
     * 设置为挂账 (PRD 4.1.3 欠费挂账)
     */
    public function setCredit(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->setCredit($id, Auth::id())
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
