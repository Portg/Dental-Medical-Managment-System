<?php

namespace App\Http\Controllers;

use App\InventoryItem;
use App\StockIn;
use App\StockInItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockInItemController extends Controller
{
    /**
     * Display a listing of items for a stock in.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->stock_in_id) {
            $data = StockInItem::with('inventoryItem')
                ->where('stock_in_id', $request->stock_in_id)
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
                ->addColumn('editBtn', function ($row) {
                    $stockIn = $row->stockIn;
                    if ($stockIn && $stockIn->isDraft()) {
                        return '<a href="#" onclick="editItem(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                    }
                    return '';
                })
                ->addColumn('deleteBtn', function ($row) {
                    $stockIn = $row->stockIn;
                    if ($stockIn && $stockIn->isDraft()) {
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
        // Verify stock in is draft
        $stockIn = StockIn::find($request->stock_in_id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'stock_in_id' => 'required|exists:stock_ins,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ], [
            'inventory_item_id.required' => __('inventory.item_required'),
            'qty.required' => __('inventory.qty_required'),
            'qty.min' => __('inventory.qty_min'),
            'unit_price.required' => __('inventory.unit_price_required'),
        ])->validate();

        // Check if item requires expiry tracking
        $inventoryItem = InventoryItem::find($request->inventory_item_id);
        if ($inventoryItem && $inventoryItem->track_expiry) {
            if (empty($request->batch_no) || empty($request->expiry_date)) {
                return response()->json([
                    'message' => __('inventory.batch_expiry_required'),
                    'status' => false
                ]);
            }
        }

        // Check price deviation (BR-043)
        if ($inventoryItem && $inventoryItem->reference_price > 0) {
            $deviation = abs($request->unit_price - $inventoryItem->reference_price) / $inventoryItem->reference_price;
            if ($deviation > 0.2 && !$request->confirm_deviation) {
                return response()->json([
                    'message' => __('inventory.price_deviation_warning'),
                    'requires_confirmation' => true,
                    'deviation_percent' => round($deviation * 100, 1),
                    'status' => 'warning'
                ]);
            }
        }

        $item = StockInItem::create([
            'stock_in_id' => $request->stock_in_id,
            'inventory_item_id' => $request->inventory_item_id,
            'qty' => $request->qty,
            'unit_price' => $request->unit_price,
            'amount' => $request->qty * $request->unit_price,
            'batch_no' => $request->batch_no,
            'expiry_date' => $request->expiry_date,
            'production_date' => $request->production_date,
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
        $item = StockInItem::with('inventoryItem')->find($id);
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
        $item = StockInItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock in is draft
        $stockIn = $item->stockIn;
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ])->validate();

        // Check if item requires expiry tracking
        $inventoryItem = $item->inventoryItem;
        if ($inventoryItem && $inventoryItem->track_expiry) {
            if (empty($request->batch_no) || empty($request->expiry_date)) {
                return response()->json([
                    'message' => __('inventory.batch_expiry_required'),
                    'status' => false
                ]);
            }
        }

        $status = $item->update([
            'qty' => $request->qty,
            'unit_price' => $request->unit_price,
            'amount' => $request->qty * $request->unit_price,
            'batch_no' => $request->batch_no,
            'expiry_date' => $request->expiry_date,
            'production_date' => $request->production_date,
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
        $item = StockInItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock in is draft
        $stockIn = $item->stockIn;
        if (!$stockIn || !$stockIn->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        $status = $item->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
