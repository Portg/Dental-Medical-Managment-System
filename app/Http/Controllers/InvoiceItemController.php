<?php

namespace App\Http\Controllers;

use App\Services\InvoiceItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoiceItemController extends Controller
{
    private InvoiceItemService $invoiceItemService;

    public function __construct(InvoiceItemService $invoiceItemService)
    {
        $this->invoiceItemService = $invoiceItemService;

        // 诊疗页「牙科账单」Tab 按 view-invoices 展示；列表接口若仍要求
        // edit-invoices，医生等只读角色会 DataTables Ajax 403。
        $this->middleware('can:view-invoices')->only(['index', 'appointmentInvoiceItems', 'show']);
        $this->middleware('can:edit-invoices')->only(['create', 'store', 'edit', 'update', 'destroy']);
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

            $data = $this->invoiceItemService->getItemsByInvoice((int) $invoice_id);
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
                    if (!Gate::allows('edit-invoices')) {
                        return '';
                    }
                    return '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    if (!Gate::allows('edit-invoices')) {
                        return '';
                    }
                    return '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    //this applies on the doctor's invoicing dashboard
    public function appointmentInvoiceItems(Request $request, $appointment_id)
    {
        if ($request->ajax()) {

            $data = $this->invoiceItemService->getItemsByAppointment((int) $appointment_id);
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
                    if (!Gate::allows('edit-invoices')) {
                        return '';
                    }
                    return '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    if (!Gate::allows('edit-invoices')) {
                        return '';
                    }
                    return '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }

        return response()->json(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
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
        return response()->json($this->invoiceItemService->getItemForEdit((int) $id));
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

        $status = $this->invoiceItemService->updateItem((int) $id, $request->only(['price', 'qty', 'doctor_id', 'medical_service_id']));
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
        $status = $this->invoiceItemService->deleteItem((int) $id);
        if ($status) {
            return response()->json(['message' => __('invoices.invoice_item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
