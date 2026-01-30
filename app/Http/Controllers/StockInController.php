<?php

namespace App\Http\Controllers;

use App\Branch;
use App\InventoryBatch;
use App\InventoryItem;
use App\StockIn;
use App\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockInController extends Controller
{
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
            $query = StockIn::with(['supplier', 'addedBy'])
                ->orderBy('stock_in_date', 'DESC')
                ->orderBy('id', 'DESC');

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('stock_in_date', [$request->start_date, $request->end_date]);
            }

            $data = $query->get();

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

        $data['suppliers'] = Supplier::orderBy('name')->get();
        return view('inventory.stock_ins.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['suppliers'] = Supplier::orderBy('name')->get();
        $data['branches'] = Branch::orderBy('name')->get();
        $data['stock_in_no'] = StockIn::generateStockInNo();
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

        $stockIn = StockIn::create([
            'stock_in_no' => StockIn::generateStockInNo(),
            'supplier_id' => $request->supplier_id,
            'stock_in_date' => $request->stock_in_date,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
            'status' => 'draft',
            '_who_added' => Auth::User()->id
        ]);

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
        $data['stockIn'] = StockIn::with(['supplier', 'items.inventoryItem', 'addedBy'])->find($id);
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
        $data['stockIn'] = StockIn::with(['supplier', 'items.inventoryItem'])->find($id);
        if (!$data['stockIn'] || !$data['stockIn']->isDraft()) {
            abort(404);
        }
        $data['suppliers'] = Supplier::orderBy('name')->get();
        $data['branches'] = Branch::orderBy('name')->get();
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
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'stock_in_date' => 'required|date',
        ])->validate();

        $status = $stockIn->update([
            'supplier_id' => $request->supplier_id,
            'stock_in_date' => $request->stock_in_date,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
        ]);

        if ($status) {
            return response()->json(['message' => __('inventory.stock_in_updated_successfully'), 'status' => true]);
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
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_delete_confirmed'), 'status' => false]);
        }

        $status = $stockIn->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.stock_in_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Confirm the stock in and update inventory.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function confirm($id)
    {
        $stockIn = StockIn::with('items.inventoryItem')->find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_confirm'), 'status' => false]);
        }

        if ($stockIn->items()->count() == 0) {
            return response()->json(['message' => __('inventory.no_items_to_confirm'), 'status' => false]);
        }

        DB::beginTransaction();
        try {
            foreach ($stockIn->items as $item) {
                $inventoryItem = $item->inventoryItem;
                $oldStock = $inventoryItem->current_stock;
                $oldCost = $inventoryItem->average_cost;
                $newStock = $oldStock + $item->qty;

                // Calculate weighted average cost
                $newCost = $oldStock == 0
                    ? $item->unit_price
                    : (($oldStock * $oldCost) + ($item->qty * $item->unit_price)) / $newStock;

                $inventoryItem->update([
                    'current_stock' => $newStock,
                    'average_cost' => $newCost
                ]);

                // Create batch record
                InventoryBatch::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'batch_no' => $item->batch_no ?? ('B' . date('YmdHis') . $item->id),
                    'expiry_date' => $item->expiry_date,
                    'production_date' => $item->production_date,
                    'qty' => $item->qty,
                    'unit_cost' => $item->unit_price,
                    'stock_in_id' => $stockIn->id,
                    'status' => 'available',
                    '_who_added' => Auth::User()->id
                ]);
            }

            $stockIn->update(['status' => 'confirmed']);

            DB::commit();
            return response()->json(['message' => __('inventory.stock_in_confirmed'), 'status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
        }
    }

    /**
     * Cancel the stock in.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_cancel'), 'status' => false]);
        }

        $status = $stockIn->update(['status' => 'cancelled']);
        if ($status) {
            return response()->json(['message' => __('inventory.stock_in_cancelled'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
