<?php

namespace App\Http\Controllers;

use App\Services\InventoryItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryItemController extends Controller
{
    private InventoryItemService $service;

    public function __construct(InventoryItemService $service)
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
            $data = $this->service->getItemList($request->only(['category_id', 'is_active']));

            return $this->service->buildIndexDataTable($data);
        }

        $data['categories'] = $this->service->getActiveCategories();
        return view('inventory.items.index')->with($data);
    }

    /**
     * Search inventory items for autocomplete.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        if ($request->q) {
            return response()->json($this->service->searchItems($request->q));
        }

        return response()->json([]);
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
            'item_code' => 'required|unique:inventory_items,item_code|max:50',
            'name' => 'required|max:255',
            'unit' => 'required|max:50',
            'category_id' => 'required|exists:inventory_categories,id',
        ], [
            'item_code.required' => __('inventory.item_code_required'),
            'item_code.unique' => __('inventory.item_code_unique'),
            'name.required' => __('inventory.item_name_required'),
            'unit.required' => __('inventory.unit_required'),
            'category_id.required' => __('inventory.category_required'),
        ])->validate();

        $item = $this->service->createItem($request->only([
            'item_code', 'name', 'unit', 'category_id', 'specification', 'brand',
            'reference_price', 'selling_price', 'track_expiry', 'stock_warning_level',
            'storage_location', 'notes', 'is_active',
        ]));

        if ($item) {
            return response()->json(['message' => __('inventory.item_added_successfully'), 'status' => true]);
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
        return response()->json($this->service->getItemForEdit($id));
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
            'item_code' => 'required|unique:inventory_items,item_code,' . $id . '|max:50',
            'name' => 'required|max:255',
            'unit' => 'required|max:50',
            'category_id' => 'required|exists:inventory_categories,id',
        ], [
            'item_code.required' => __('inventory.item_code_required'),
            'item_code.unique' => __('inventory.item_code_unique'),
            'name.required' => __('inventory.item_name_required'),
            'unit.required' => __('inventory.unit_required'),
            'category_id.required' => __('inventory.category_required'),
        ])->validate();

        $status = $this->service->updateItem($id, $request->only([
            'item_code', 'name', 'unit', 'category_id', 'specification', 'brand',
            'reference_price', 'selling_price', 'track_expiry', 'stock_warning_level',
            'storage_location', 'notes', 'is_active',
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
        $result = $this->service->deleteItem($id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Display stock warnings page.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function stockWarnings(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getLowStockItems();

            return $this->service->buildStockWarningsDataTable($data);
        }

        return view('inventory.items.stock_warnings');
    }

    /**
     * Display expiry warnings page.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function expiryWarnings(Request $request)
    {
        if ($request->ajax()) {
            $warningDays = $request->warning_days ?? 30;
            $data = $this->service->getExpiryWarningBatches($warningDays);

            return $this->service->buildExpiryWarningsDataTable($data);
        }

        return view('inventory.items.expiry_warnings');
    }
}
