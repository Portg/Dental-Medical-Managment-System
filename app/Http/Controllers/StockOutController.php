<?php

namespace App\Http\Controllers;

use App\Services\StockOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockOutController extends Controller
{
    private StockOutService $service;

    public function __construct(StockOutService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-inventory');
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
            $data = $this->service->getStockOutList($request->only(['status', 'out_type', 'start_date', 'end_date']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('out_type_label', function ($row) {
                    return $row->out_type_label;
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->fullname : '-';
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
                    return '<a href="' . route('stock-outs.show', $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="' . route('stock-outs.edit', $row->id) . '" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
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

        return view('inventory.stock_outs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->service->getCreateFormData();
        return view('inventory.stock_outs.create')->with($data);
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
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:treatment,department,damage,other',
        ], [
            'stock_out_date.required' => __('inventory.stock_out_date_required'),
            'out_type.required' => __('inventory.out_type_required'),
        ])->validate();

        $stockOut = $this->service->createStockOut($request->only([
            'stock_out_date', 'out_type', 'patient_id', 'appointment_id',
            'department', 'notes', 'branch_id',
        ]));

        if ($stockOut) {
            return response()->json([
                'message' => __('inventory.stock_out_created_successfully'),
                'status' => true,
                'redirect' => route('stock-outs.edit', $stockOut->id)
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
        $data['stockOut'] = $this->service->getStockOutDetail((int) $id);
        if (!$data['stockOut']) {
            abort(404);
        }
        return view('inventory.stock_outs.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->service->getStockOutForEdit((int) $id);
        if (!$data) {
            abort(404);
        }
        return view('inventory.stock_outs.create')->with($data);
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
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:treatment,department,damage,other',
        ])->validate();

        $result = $this->service->updateStockOut((int) $id, $request->only([
            'stock_out_date', 'out_type', 'patient_id', 'appointment_id',
            'department', 'notes', 'branch_id',
        ]));
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
        $result = $this->service->deleteStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Confirm the stock out and update inventory.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function confirm($id)
    {
        $result = $this->service->confirmStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Cancel the stock out.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $result = $this->service->cancelStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }
}
