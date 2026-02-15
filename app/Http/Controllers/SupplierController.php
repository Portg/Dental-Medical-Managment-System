<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SupplierController extends Controller
{
    private SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->supplierService->getSupplierList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->AddedBy->othername;
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('suppliers.index');
    }

    public function filterSuppliers(Request $request)
    {
        echo json_encode($this->supplierService->filterSuppliers());
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => __('validation.custom.name.required'),
        ])->validate();

        $status = $this->supplierService->createSupplier($request->all());

        if ($status) {
            return response()->json(['message' => __('common.supplier_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        return response()->json($this->supplierService->getSupplier($id));
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => __('validation.custom.name.required'),
        ])->validate();

        $status = $this->supplierService->updateSupplier($id, $request->all());

        if ($status) {
            return response()->json(['message' => __('common.supplier_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function destroy($id)
    {
        $status = $this->supplierService->deleteSupplier($id);

        if ($status) {
            return response()->json(['message' => __('common.supplier_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
