<?php

namespace App\Http\Controllers;

use App;
use App\Http\Helper\FunctionsHelper;
use App\Invoice;
use App\InvoiceItem;
use App\InvoicePayment;
use App\MedicalService;
use Excel;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDF;
use Yajra\DataTables\DataTables;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    protected $medical_service_id;

    /**
     * InvoiceController constructor.
     * @param $medical_service_id
     */
    public function __construct()
    {
        $this->medical_service_id = '';
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {


            if (!empty($_GET['search'])) {
                $data = DB::table('invoices')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->join('users', 'users.id', 'invoices._who_added')
                    ->where('patients.surname', 'like', '%' . $request->get('search') . '%')
                    ->orWhere('patients.othername', 'like', '%' . $request->get('search') . '%')
                    ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
                    ->OrderBy('invoices.id', 'desc')
                    ->get();
            } else if (!empty($_GET['invoice_no'])) {
                $data = DB::table('invoices')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->join('users', 'users.id', 'invoices._who_added')
                    ->where('invoices.invoice_no', '=', $request->invoice_no)
                    ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
                    ->OrderBy('invoices.id', 'desc')
                    ->get();
            } else if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {

                FunctionsHelper::storeDateFilter($request);
                $data = DB::table('invoices')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->join('users', 'users.id', 'invoices._who_added')
                    ->whereBetween(DB::raw('DATE_FORMAT(invoices.created_at, \'%Y-%m-%d\')'), array
                    ($request->start_date,
                        $request->end_date))
                    ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
                    ->OrderBy('invoices.id', 'desc')
                    ->get();
            } else {
                $data = DB::table('invoices')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->join('users', 'users.id', 'invoices._who_added')
                    ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
                    ->OrderBy('invoices.id', 'desc')
                    ->get();
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('invoice_no', function ($row) {
                    return '<a href="' . url('invoices/' . $row->id) . '">' . $row->invoice_no . '</a>';
                })
                ->addColumn('customer', function ($row) {
                    return $row->surname . " " . $row->othername;
                })
                ->addColumn('amount', function ($row) {
                    return number_format($this->TotalInvoiceAmount($row->id));
                })
                ->addColumn('paid_amount', function ($row) {
                    return number_format($this->TotalInvoicePaidAmount($row->id));
                })
                ->addColumn('due_amount', function ($row) {
                    //check if customer has fully paid for the invoice
                    if ($this->InvoiceBalance($row->id) <= 0) {
                        return number_format($this->InvoiceBalance($row->id));
                    }
                    return number_format($this->InvoiceBalance($row->id)) . '<br>
                    <a href="#" onclick="record_payment(' . $row->id . ')" class="text-primary">' . __('invoices.record_payment') . '</a>
                    ';
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
                    return $btn;
                })
                ->rawColumns(['invoice_no', 'due_amount', 'payment_classification', 'action', 'status'])
                ->make(true);
        }
        return view('invoices.index');
    }

    public function previewInvoice($invoice_id)
    {
        $invoice = Invoice::where('id', $invoice_id)->first();
        //get invoice items
        $invoice_items = InvoiceItem::where('invoice_id', $invoice_id)->get();
        return view('invoices.preview', compact('invoice', 'invoice_items'));
    }

    public function invoiceShareDetails(Request $request, $invoice_id)
    {
        $invoice = DB::table('invoices')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'invoices._who_added')
            ->where('invoices.id', $invoice_id)
            ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
            ->OrderBy('invoices.id', 'desc')
            ->first();
        return response()->json($invoice);
    }

    public function sendInvoice(Request $request)
    {
        Validator::make($request->all(), [
            'invoice_id' => 'required',
            'email' => 'required'
        ], [
            'invoice_id.required' => __('validation.attributes.invoice_id') . ' ' . __('validation.required'),
            'email.required' => __('validation.attributes.email') . ' ' . __('validation.required'),
        ])->validate();

        $data['patient'] = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('invoices.id', $request->invoice_id)
            ->select('surname', 'othername', 'email', 'phone_no')
            ->first();

        $data['invoice'] = Invoice::where('id', $request->invoice_id)->first();
        $data['invoice_items'] = InvoiceItem::where('invoice_id', $request->invoice_id)->get();
        $data['payments'] = InvoicePayment::where('invoice_id', $request->invoice_id)->get();

        dispatch(new App\Jobs\ShareEmailInvoice($data, $request->email, $request->message));
        return response()->json(['message' => __('emails.invoice_sent_successfully'), 'status' => true]);
    }


    public function invoiceAmount($invoice_id) //used to auto show the invoice balance on the payment clearance view
    {
        //get invoice info
        $invoice = Invoice::findOrfail($invoice_id);

        $data['amount'] = $this->InvoiceBalance($invoice_id);
        $data['today_date'] = date('Y-m-d');
        $data['patient'] = $this->patient_info($invoice->appointment_id);
        return response()->json($data);
    }


    /**
     * Patient-specific invoices for patient detail page.
     */
    public function patientInvoices(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = DB::table('invoices')
                ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->whereNull('invoices.deleted_at')
                ->where('patients.id', $patient_id)
                ->select(
                    'invoices.*',
                    'appointments.status as appointment_status'
                )
                ->orderBy('invoices.created_at', 'desc')
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-';
                })
                ->addColumn('amount', function ($row) {
                    return number_format($this->TotalInvoiceAmount($row->id));
                })
                ->addColumn('statusBadge', function ($row) {
                    $balance = $this->InvoiceBalance($row->id);
                    $total = $this->TotalInvoiceAmount($row->id);
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

    private function InvoiceBalance($invoice_id)
    {

        $balance = $this->TotalInvoiceAmount($invoice_id) - $this->TotalInvoicePaidAmount($invoice_id);
        return $balance;
    }

    //cash amount paid
    private function CashAmountPaid($invoice_id)
    {
        //cash amount
        $cash_amount = InvoicePayment::where(['invoice_id' => $invoice_id, 'payment_method' => 'Cash'])->sum('amount');
        return $cash_amount;
    }

    private function SelfAccountAmountPaid($invoice_id)
    {
        //self account amount
        $cash_amount = InvoicePayment::where(['invoice_id' => $invoice_id, 'payment_method' => 'Self Account'])->sum('amount');
        return $cash_amount;
    }

    //insurance amount paid
    private function InsuranceAmountPaid($invoice_id)
    {
        $insurance_amount = InvoicePayment::where(['invoice_id' => $invoice_id, 'payment_method' => 'Insurance'])->sum('amount');
        return $insurance_amount;
    }

    //using appointments to get the patient details
    private function patient_info($appointment_id)
    {
        $data = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
            ->where('appointments.id', $appointment_id)
            ->select('patients.*', 'insurance_companies.name')
            ->first();
        return $data;
    }

    public function printReceipt($invoice_id)
    {
        $data['patient'] = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('invoices.id', $invoice_id)
            ->select('patients.*')
            ->first();
        $data['invoice'] = Invoice::where('id', $invoice_id)->first();
        $data['invoice_items'] = InvoiceItem::where('invoice_id', $invoice_id)->get();
        $data['payments'] = InvoicePayment::where('invoice_id', $invoice_id)->get();

        $pdf = PDF::loadView('invoices.receipt_print', $data);
        return $pdf->stream('receipt', array("attachment" => false))->header('Content-Type', 'application/pdf');


    }


    public function exportReport(Request $request)
    {

        if ($request->session()->get('from') != '' && $request->session()->get('to') != '') {
            $queryBuilder =
                DB::table('invoices')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->join('users', 'users.id', 'invoices._who_added')
                    ->whereBetween(DB::raw('DATE(invoices.created_at)'), array($request->session()->get('from'),
                        $request->session()->get('to')))
                    ->select('invoices.*', 'patients.surname', 'patients.othername', 'users.othername as addedBy')
                    ->OrderBy('invoices.id', 'ASC')
                    ->get();

        } else {
            $queryBuilder = DB::table('invoices')
                ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->join('users', 'users.id', 'invoices._who_added')
                ->select('invoices.*', 'patients.surname', 'patients.othername', 'users.othername as addedBy')
                ->OrderBy('invoices.id', 'ASC')
                ->get();
        }


        $excel_file_name = "Invoicing-report- " . time();
        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::create($excel_file_name, function ($excel) use ($queryBuilder, $sheet_title) {

            $excel->sheet($sheet_title, function ($sheet) use ($queryBuilder) {
                $payload = [];
                $count_rows = 2;
                $grand_total = 0;
                $grand_total_paid = 0;
                $grand_outstanding = 0;

                foreach ($queryBuilder as $row) {
                    $payload[] = array('Invoice No' => $row->invoice_no,
                        'Invoice Date' => date('d-M-Y', strtotime($row->created_at)),
                        'Patient Name' => $row->surname . " " . $row->othername,
                        'Total Amount' => $this->TotalInvoiceAmount($row->id),
                        'invoice_procedures' => $this->invoiceProcedures($row->id),
                        'Paid Amount' => $this->TotalInvoicePaidAmount($row->id),
                        'Outstanding Balance' => $this->InvoiceBalance($row->id));
                    $count_rows++;
                    $grand_total = $grand_total + $this->TotalInvoiceAmount($row->id);
                    $grand_total_paid = $grand_total_paid + $this->TotalInvoicePaidAmount($row->id);
                    $grand_outstanding = $grand_outstanding + $this->InvoiceBalance($row->id);
                }
                //general invoices totals
                $sheet->cell('D' . $count_rows, function ($cell) use ($grand_total) {
                    $cell->setValue('Total= ' . number_format($grand_total));
                    $cell->setFontWeight('bold');
                });
                //grand total paid amounts
                $sheet->cell('E' . $count_rows, function ($cell) use ($grand_total_paid) {
                    $cell->setValue('Total Paid = ' . number_format($grand_total_paid));
                    $cell->setFontWeight('bold');
                });
                //grand outstanding balances
                $sheet->cell('F' . $count_rows, function ($cell) use ($grand_outstanding) {
                    $cell->setValue('Total Outstanding = ' . number_format($grand_outstanding));
                    $cell->setFontWeight('bold');
                });
                $sheet->fromArray($payload);
            });

        })->download('xls');

    }
    public function invoiceProceduresToJson($InvoiceId){
       $InvoiceProcedures = DB::table("invoice_items")
       ->leftjoin("medical_services", "medical_services.id", "invoice_items.medical_service_id")
       ->whereNull("invoice_items.deleted_at")
       ->where("invoice_items.invoice_id", $InvoiceId)
       ->select("medical_services.name", "invoice_items.qty","invoice_items.price",DB::raw("invoice_items.qty*invoice_items.price as total"))
       ->get();
        return Response()->json($InvoiceProcedures); 
    }

    private function invoiceProcedures($invoice_id)
    {
        //get the list procedure done by the doctor
        $procedures = DB::table("invoice_items")->leftjoin("medical_services", "medical_services.id", "invoice_items.medical_service_id")->whereNull("invoice_items.deleted_at")->where("invoice_items.invoice_id", $invoice_id)->select("medical_services.name", "invoice_items.*")->get();
        $procedure = "";
        foreach ($procedures as $value) {
            $procedure .= $value->name;
        }
        return $procedure;
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

        //check if the appointment ready has invoice
        $invoice_old = Invoice::where('appointment_id', $request->appointment_id)->first();
        if ($invoice_old == null) {
            $invoice = Invoice::create(
                [
                    'invoice_no' => Invoice::InvoiceNo(),
                    'appointment_id' => $request->appointment_id,
                    '_who_added' => Auth::User()->id,
                ]
            );
            if ($invoice) {
                foreach ($request->addmore as $key => $value) {
                    //get service id
                    InvoiceItem::create([
                        'qty' => $value['qty'],
                        'price' => $value['price'],
                        'invoice_id' => $invoice->id,
                        'tooth_no' => $value['tooth_no'],
                        'medical_service_id' => $value['medical_service_id'],
                        'doctor_id' => $value['doctor_id'],
                        '_who_added' => Auth::User()->id,
                    ]);
                }
                return response()->json(['message' => __('invoices.invoice_created_successfully'), 'status' => true]);
            }
        } else {
            //invoice has already been created so add new invoice items
            foreach ($request->addmore as $key => $value) {
                InvoiceItem::create([
                    'qty' => $value['qty'],
                    'price' => $value['price'],
                    'invoice_id' => $invoice_old->id,
                    'medical_service_id' => $value['medical_service_id'],
                    'doctor_id' => $value['doctor_id'],
                    '_who_added' => Auth::User()->id,
                ]);
            }
            return response()->json(['message' => __('invoices.invoice_created_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function show($invoice)
    {
        $data['patient'] =
            DB::table('invoices')
                ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->where('invoices.id', $invoice)
                ->select('patients.*')
                ->first();
        $data['invoice_id'] = $invoice;
        $data['invoice'] = Invoice::where('id', $invoice)->first();
        return view('invoices.show.index')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => __('invoices.no_invoices_found'), 'status' => false]);
        }

        // Soft delete the invoice (the model uses SoftDeletes trait)
        $status = $invoice->delete();

        if ($status) {
            return response()->json(['message' => __('invoices.invoice_deleted_successfully'), 'status' => true]);
        }

        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    private function generateServiceId($medical_service)
    {

        //first check if the service exists or insert new
        $service = MedicalService::where('name', $medical_service)->first();
        if ($service != null) {
            $this->medical_service_id = $service->id;
        }
//        else {
//            //insert the new service
//            $new_service = MedicalService::create(
//                [
//                    'name' => $medical_service,
//                    '_who_added' => Auth::User()->id
//                ]);
//            $this->medical_service_id = $new_service->id;
//        }
        return $this->medical_service_id;
    }

    private function TotalInvoiceAmount($id)
    {
        return InvoiceItem::where('invoice_id', $id)->sum(DB::raw('price*qty'));
    }

    private function TotalInvoicePaidAmount($id)
    {
        return InvoicePayment::where('invoice_id', $id)->sum('amount');
    }

    /**
     * 待折扣审批列表 (PRD 4.1.2 BR-035)
     */
    public function pendingDiscountApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = Invoice::pendingDiscountApproval()
                ->with(['patient', 'addedBy'])
                ->orderBy('created_at', 'asc')
                ->get();

            return \Yajra\DataTables\DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('invoice_no', function ($row) {
                    return '<a href="' . url('invoices/' . $row->id) . '">' . $row->invoice_no . '</a>';
                })
                ->addColumn('patient_name', function ($row) {
                    if ($row->patient) {
                        return $row->patient->surname . ' ' . $row->patient->othername;
                    }
                    return '-';
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
        $invoice = Invoice::findOrFail($id);

        if ($invoice->discount_approval_status !== 'pending') {
            return response()->json([
                'message' => __('invoices.discount_not_pending'),
                'status' => false
            ]);
        }

        $invoice->approveDiscount(Auth::id(), $request->reason);

        return response()->json([
            'message' => __('invoices.discount_approved'),
            'status' => true
        ]);
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
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        $invoice = Invoice::findOrFail($id);

        if ($invoice->discount_approval_status !== 'pending') {
            return response()->json([
                'message' => __('invoices.discount_not_pending'),
                'status' => false
            ]);
        }

        $invoice->rejectDiscount(Auth::id(), $request->reason);

        return response()->json([
            'message' => __('invoices.discount_rejected'),
            'status' => true
        ]);
    }

    /**
     * 设置为挂账 (PRD 4.1.3 欠费挂账)
     */
    public function setCredit(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->payment_status === 'paid') {
            return response()->json([
                'message' => __('invoices.invoice_already_paid'),
                'status' => false
            ]);
        }

        $invoice->setAsCredit(Auth::id());

        return response()->json([
            'message' => __('invoices.credit_approved'),
            'status' => true
        ]);
    }

    /**
     * 搜索发票 (用于退费页面)
     */
    public function searchInvoices(Request $request)
    {
        $search = $request->get('q', '');

        $invoices = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'))
            ->where(function ($query) use ($search) {
                $query->where('invoices.invoice_no', 'like', "%{$search}%")
                    ->orWhere('patients.surname', 'like', "%{$search}%")
                    ->orWhere('patients.othername', 'like', "%{$search}%");
            })
            ->whereNull('invoices.deleted_at')
            ->select(
                'invoices.id',
                'invoices.invoice_no',
                DB::raw("CONCAT(patients.surname, ' ', patients.othername) as patient_name")
            )
            ->limit(20)
            ->get();

        return response()->json($invoices);
    }
}
