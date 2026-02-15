<?php

namespace App\Http\Controllers;

use App\Services\InventoryItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InventoryItemController extends Controller
{
    private InventoryItemService $service;

    public function __construct(InventoryItemService $service)
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
            $data = $this->service->getItemList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('category_name', function ($row) {
                    return $row->category ? $row->category->name : '-';
                })
                ->addColumn('stock_status', function ($row) {
                    if ($row->isLowStock()) {
                        return '<span class="badge badge-danger">' . __('inventory.low_stock') . '</span>';
                    }
                    return '<span class="badge badge-success">' . __('inventory.in_stock') . '</span>';
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge badge-success">' . __('common.active') . '</span>';
                    }
                    return '<span class="badge badge-secondary">' . __('common.inactive') . '</span>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['stock_status', 'status', 'editBtn', 'deleteBtn'])
                ->make(true);
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

        $item = $this->service->createItem($request->all());

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

        $status = $this->service->updateItem($id, $request->all());

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

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('category_name', function ($row) {
                    return $row->category ? $row->category->name : '-';
                })
                ->addColumn('shortage', function ($row) {
                    return $row->stock_warning_level - $row->current_stock;
                })
                ->rawColumns([])
                ->make(true);
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

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('item_code', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->item_code : '-';
                })
                ->addColumn('item_name', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->name : '-';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->inventoryItem && $row->inventoryItem->category
                        ? $row->inventoryItem->category->name : '-';
                })
                ->addColumn('days_to_expiry', function ($row) {
                    return $row->days_to_expiry;
                })
                ->addColumn('expiry_status', function ($row) {
                    if ($row->isExpired()) {
                        return '<span class="badge badge-danger">' . __('inventory.expired') . '</span>';
                    }
                    if ($row->days_to_expiry <= 7) {
                        return '<span class="badge badge-warning">' . __('inventory.expiring_soon') . '</span>';
                    }
                    return '<span class="badge badge-info">' . __('inventory.near_expiry') . '</span>';
                })
                ->rawColumns(['expiry_status'])
                ->make(true);
        }

        return view('inventory.items.expiry_warnings');
    }
}
