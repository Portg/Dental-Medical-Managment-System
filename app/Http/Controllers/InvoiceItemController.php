<?php

namespace App\Http\Controllers;

use App\Services\InvoiceItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoiceItemController extends Controller
{
    private InvoiceItemService $invoiceItemService;

    public function __construct(InvoiceItemService $invoiceItemService)
    {
        $this->invoiceItemService = $invoiceItemService;

        $this->middleware('can:edit-invoices');
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

            $data = $this->invoiceItemService->getItemsByInvoice($invoice_id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('service', function ($row) {
                    return $row->medical_service->name;
                })
                ->addColumn('qty', function ($row) {
                    return number_format($row->qty);
                })
                ->addColumn('price', function ($row) {
                    return number_format($row->price);
                })
                ->addColumn('total_amount', function ($row) {
                    return number_format($row->price * $row->qty);
                })
                ->addColumn('procedure_doctor', function ($row) {
                    return $row->procedure_doctor->surname;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    //this applies on the doctor's invoicing dashboard
    public function appointmentInvoiceItems(Request $request, $appointment_id)
    {
        if ($request->ajax()) {

            $data = $this->invoiceItemService->getItemsByAppointment($appointment_id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('service', function ($row) {
                    return $row->service_name;
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
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
        //
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->invoiceItemService->getItemForEdit($id));
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
            'price' => 'required',
            'qty' => 'required',
            'doctor_id' => 'required',
            'medical_service_id' => 'required'
        ])->validate();

        $status = $this->invoiceItemService->updateItem($id, $request->only(['price', 'qty', 'doctor_id', 'medical_service_id']));
        if ($status) {
            return response()->json(['message' => __('invoices.invoice_item_updated_successfully'), 'status' => true]);
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
        $status = $this->invoiceItemService->deleteItem($id);
        if ($status) {
            return response()->json(['message' => __('invoices.invoice_item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
