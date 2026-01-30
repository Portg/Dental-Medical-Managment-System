<?php

namespace App\Http\Controllers;

use App\InventoryItem;
use App\StockOut;
use App\StockOutItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockOutItemController extends Controller
{
    /**
     * Display a listing of items for a stock out.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->stock_out_id) {
            $data = StockOutItem::with('inventoryItem')
                ->where('stock_out_id', $request->stock_out_id)
                ->orderBy('id')
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('item_code', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->item_code : '-';
                })
                ->addColumn('item_name', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->name : '-';
                })
                ->addColumn('specification', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->specification : '-';
                })
                ->addColumn('unit', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->unit : '-';
                })
                ->addColumn('current_stock', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->current_stock : '-';
                })
                ->addColumn('editBtn', function ($row) {
                    $stockOut = $row->stockOut;
                    if ($stockOut && $stockOut->isDraft()) {
                        return '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                    }
                    return '';
                })
                ->addColumn('deleteBtn', function ($row) {
                    $stockOut = $row->stockOut;
                    if ($stockOut && $stockOut->isDraft()) {
                        return '<a href="#" onclick="deleteItem(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    }
                    return '';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Verify stock out is draft
        $stockOut = StockOut::find($request->stock_out_id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'stock_out_id' => 'required|exists:stock_outs,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'qty' => 'required|numeric|min:0.01',
        ], [
            'inventory_item_id.required' => __('inventory.item_required'),
            'qty.required' => __('inventory.qty_required'),
            'qty.min' => __('inventory.qty_min'),
        ])->validate();

        // Check stock availability
        $inventoryItem = InventoryItem::find($request->inventory_item_id);
        if ($inventoryItem->current_stock < $request->qty) {
            return response()->json([
                'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                'status' => false
            ]);
        }

        // Use average cost as unit cost
        $unitCost = $inventoryItem->average_cost;

        $item = StockOutItem::create([
            'stock_out_id' => $request->stock_out_id,
            'inventory_item_id' => $request->inventory_item_id,
            'qty' => $request->qty,
            'unit_cost' => $unitCost,
            'amount' => $request->qty * $unitCost,
            'batch_no' => $request->batch_no,
            '_who_added' => Auth::User()->id
        ]);

        if ($item) {
            return response()->json([
                'message' => __('inventory.item_added_successfully'),
                'status' => true,
                'item' => $item
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $item = StockOutItem::with('inventoryItem')->find($id);
        return response()->json($item);
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
        $item = StockOutItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock out is draft
        $stockOut = $item->stockOut;
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'qty' => 'required|numeric|min:0.01',
        ])->validate();

        // Check stock availability
        $inventoryItem = $item->inventoryItem;
        if ($inventoryItem->current_stock < $request->qty) {
            return response()->json([
                'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                'status' => false
            ]);
        }

        $status = $item->update([
            'qty' => $request->qty,
            'amount' => $request->qty * $item->unit_cost,
            'batch_no' => $request->batch_no,
        ]);

        if ($status) {
            return response()->json(['message' => __('inventory.item_updated_successfully'), 'status' => true]);
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
        $item = StockOutItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock out is draft
        $stockOut = $item->stockOut;
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        $status = $item->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
