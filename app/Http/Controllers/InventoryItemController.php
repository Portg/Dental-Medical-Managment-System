<?php

namespace App\Http\Controllers;

use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InventoryItemController extends Controller
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
            $query = InventoryItem::with('category')
                ->orderBy('updated_at', 'DESC');

            // Filter by category
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by active status
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', $request->is_active);
            }

            $data = $query->get();

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

        $data['categories'] = InventoryCategory::active()->ordered()->get();
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
        $search = $request->q;

        if ($search) {
            $items = InventoryItem::active()
                ->search($search)
                ->with('category')
                ->limit(20)
                ->get();

            $formatted = [];
            foreach ($items as $item) {
                $formatted[] = [
                    'id' => $item->id,
                    'text' => $item->item_code . ' - ' . $item->name,
                    'item_code' => $item->item_code,
                    'name' => $item->name,
                    'specification' => $item->specification,
                    'unit' => $item->unit,
                    'reference_price' => $item->reference_price,
                    'selling_price' => $item->selling_price,
                    'current_stock' => $item->current_stock,
                    'average_cost' => $item->average_cost,
                    'track_expiry' => $item->track_expiry,
                    'category' => $item->category ? $item->category->name : null,
                ];
            }

            return response()->json($formatted);
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

        $status = InventoryItem::create([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'specification' => $request->specification,
            'unit' => $request->unit,
            'category_id' => $request->category_id,
            'brand' => $request->brand,
            'reference_price' => $request->reference_price ?? 0,
            'selling_price' => $request->selling_price ?? 0,
            'track_expiry' => $request->track_expiry ?? false,
            'stock_warning_level' => $request->stock_warning_level ?? 10,
            'storage_location' => $request->storage_location,
            'notes' => $request->notes,
            'is_active' => $request->is_active ?? true,
            '_who_added' => Auth::User()->id
        ]);

        if ($status) {
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
        $item = InventoryItem::with('category')->find($id);
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

        $status = InventoryItem::where('id', $id)->update([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'specification' => $request->specification,
            'unit' => $request->unit,
            'category_id' => $request->category_id,
            'brand' => $request->brand,
            'reference_price' => $request->reference_price ?? 0,
            'selling_price' => $request->selling_price ?? 0,
            'track_expiry' => $request->track_expiry ?? false,
            'stock_warning_level' => $request->stock_warning_level ?? 10,
            'storage_location' => $request->storage_location,
            'notes' => $request->notes,
            'is_active' => $request->is_active ?? true,
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
        // Check if item has stock movements
        $item = InventoryItem::find($id);
        if ($item && ($item->stockInItems()->count() > 0 || $item->stockOutItems()->count() > 0)) {
            return response()->json([
                'message' => __('inventory.item_has_movements'),
                'status' => false
            ]);
        }

        $status = InventoryItem::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.item_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
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
            $data = InventoryItem::lowStock()
                ->active()
                ->with('category')
                ->orderBy('current_stock')
                ->get();

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

            $data = InventoryBatch::with(['inventoryItem', 'inventoryItem.category'])
                ->where('status', 'available')
                ->where('qty', '>', 0)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', Carbon::now()->addDays($warningDays))
                ->orderBy('expiry_date')
                ->get();

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
