<?php

namespace App\Http\Controllers;

use App\Services\StockInItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockInItemController extends Controller
{
    private StockInItemService $service;

    public function __construct(StockInItemService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-inventory');
    }

    /**
     * Display a listing of items for a stock in.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->stock_in_id) {
            $data = $this->service->getItemsByStockIn((int) $request->stock_in_id);

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
        $draftError = $this->service->verifyDraftStatus((int) $request->stock_in_id);
        if ($draftError) {
            return response()->json($draftError);
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
        $expiryError = $this->service->checkExpiryRequirement(
            (int) $request->inventory_item_id, $request->batch_no, $request->expiry_date
        );
        if ($expiryError) {
            return response()->json($expiryError);
        }

        // Check price deviation (BR-043)
        $deviationWarning = $this->service->checkPriceDeviation(
            (int) $request->inventory_item_id, $request->unit_price, (bool) $request->confirm_deviation
        );
        if ($deviationWarning) {
            return response()->json($deviationWarning);
        }

        $item = $this->service->createItem($request->only([
            'stock_in_id', 'inventory_item_id', 'qty', 'unit_price',
            'batch_no', 'expiry_date', 'production_date',
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

        // Verify stock in is draft
        $draftError = $this->service->verifyItemDraftStatus($item);
        if ($draftError) {
            return response()->json($draftError);
        }

        Validator::make($request->all(), [
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ])->validate();

        // Check if item requires expiry tracking
        $expiryError = $this->service->checkItemExpiryRequirement($item, $request->batch_no, $request->expiry_date);
        if ($expiryError) {
            return response()->json($expiryError);
        }

        $status = $this->service->updateItem($item, $request->only([
            'qty', 'unit_price', 'batch_no', 'expiry_date', 'production_date',
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

        // Verify stock in is draft
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
