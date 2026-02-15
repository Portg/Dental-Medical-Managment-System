<?php

namespace App\Http\Controllers;

use App\Services\StockInService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockInController extends Controller
{
    private StockInService $service;

    public function __construct(StockInService $service)
    {
        $this->service = $service;
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
            $data = $this->service->getStockInList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('supplier_name', function ($row) {
                    return $row->supplier ? $row->supplier->name : '-';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->addedBy ? $row->addedBy->othername : '-';
                })
                ->addColumn('items_count', function ($row) {
                    return $row->items()->count();
                })
                ->addColumn('status_label', function ($row) {
                    $badges = [
                        'draft' => '<span class="badge badge-secondary">' . __('inventory.status_draft') . '</span>',
                        'confirmed' => '<span class="badge badge-success">' . __('inventory.status_confirmed') . '</span>',
                        'cancelled' => '<span class="badge badge-danger">' . __('inventory.status_cancelled') . '</span>',
                    ];
                    return $badges[$row->status] ?? $row->status;
                })
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . route('stock-ins.show', $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="' . route('stock-ins.edit', $row->id) . '" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                    }
                    return '';
                })
                ->addColumn('deleteBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    }
                    return '';
                })
                ->rawColumns(['status_label', 'viewBtn', 'editBtn', 'deleteBtn'])
                ->make(true);
        }

        $data['suppliers'] = $this->service->getSuppliers();
        return view('inventory.stock_ins.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->service->getCreateFormData();
        return view('inventory.stock_ins.create')->with($data);
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
            'stock_in_date' => 'required|date',
        ], [
            'stock_in_date.required' => __('inventory.stock_in_date_required'),
        ])->validate();

        $stockIn = $this->service->createStockIn($request->all());

        if ($stockIn) {
            return response()->json([
                'message' => __('inventory.stock_in_created_successfully'),
                'status' => true,
                'redirect' => route('stock-ins.edit', $stockIn->id)
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['stockIn'] = $this->service->getStockInDetail($id);
        if (!$data['stockIn']) {
            abort(404);
        }
        return view('inventory.stock_ins.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->service->getStockInForEdit($id);
        if (!$data) {
            abort(404);
        }
        return view('inventory.stock_ins.create')->with($data);
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
            'stock_in_date' => 'required|date',
        ])->validate();

        $result = $this->service->updateStockIn($id, $request->all());
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = $this->service->deleteStockIn($id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Confirm the stock in and update inventory.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function confirm($id)
    {
        $result = $this->service->confirmStockIn($id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Cancel the stock in.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $result = $this->service->cancelStockIn($id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }
}
