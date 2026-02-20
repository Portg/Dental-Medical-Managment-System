<?php

namespace App\Http\Controllers;

use App\Services\StockOutItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockOutItemController extends Controller
{
    private StockOutItemService $service;

    public function __construct(StockOutItemService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-inventory');
    }

    /**
     * Display a listing of items for a stock out.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->stock_out_id) {
            $data = $this->service->getItemsByStockOut((int) $request->stock_out_id);

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
        $draftError = $this->service->verifyDraftStatus((int) $request->stock_out_id);
        if ($draftError) {
            return response()->json($draftError);
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
        $stockError = $this->service->checkStockAvailability((int) $request->inventory_item_id, $request->qty);
        if ($stockError) {
            return response()->json($stockError);
        }

        $item = $this->service->createItem($request->only([
            'stock_out_id', 'inventory_item_id', 'qty', 'batch_no',
        ]));

        if ($item) {
            return response()->json([
                'message' => __('inventory.item_added_successfully'),
                'status' => true,
                'item' => $item,
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
        $item = $this->service->find((int) $id);
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
        $item = $this->service->findBasic((int) $id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock out is draft
        $draftError = $this->service->verifyItemDraftStatus($item);
        if ($draftError) {
            return response()->json($draftError);
        }

        Validator::make($request->all(), [
            'qty' => 'required|numeric|min:0.01',
        ])->validate();

        // Check stock availability
        $stockError = $this->service->checkItemStockAvailability($item, $request->qty);
        if ($stockError) {
            return response()->json($stockError);
        }

        $status = $this->service->updateItem($item, $request->only([
            'qty', 'batch_no',
        ]));

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
        $item = $this->service->findBasic((int) $id);
        if (!$item) {
            return response()->json(['message' => __('common.not_found'), 'status' => false]);
        }

        // Verify stock out is draft
        $draftError = $this->service->verifyItemDraftStatus($item);
        if ($draftError) {
            return response()->json($draftError);
        }

        $status = $this->service->deleteItem($item);
        if ($status) {
            return response()->json(['message' => __('inventory.item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
