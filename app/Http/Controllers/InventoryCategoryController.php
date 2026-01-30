<?php

namespace App\Http\Controllers;

use App\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InventoryCategoryController extends Controller
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
            $data = InventoryCategory::orderBy('sort_order')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('type_label', function ($row) {
                    return $row->type_label;
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge badge-success">' . __('common.active') . '</span>';
                    }
                    return '<span class="badge badge-secondary">' . __('common.inactive') . '</span>';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->addedBy ? $row->addedBy->othername : '-';
                })
                ->addColumn('items_count', function ($row) {
                    return $row->items()->count();
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }

        return view('inventory.categories.index');
    }

    /**
     * Get list of categories for dropdowns.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $categories = InventoryCategory::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'type']);

        return response()->json($categories);
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
            'name' => 'required|max:255',
            'code' => 'required|unique:inventory_categories,code|max:50',
            'type' => 'required|in:drug,consumable,instrument,dental_material,office',
        ], [
            'name.required' => __('inventory.category_name_required'),
            'code.required' => __('inventory.category_code_required'),
            'code.unique' => __('inventory.category_code_unique'),
            'type.required' => __('inventory.category_type_required'),
        ])->validate();

        $status = InventoryCategory::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
            '_who_added' => Auth::User()->id
        ]);

        if ($status) {
            return response()->json(['message' => __('inventory.category_added_successfully'), 'status' => true]);
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
        $category = InventoryCategory::find($id);
        return response()->json($category);
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
            'name' => 'required|max:255',
            'code' => 'required|unique:inventory_categories,code,' . $id . '|max:50',
            'type' => 'required|in:drug,consumable,instrument,dental_material,office',
        ], [
            'name.required' => __('inventory.category_name_required'),
            'code.required' => __('inventory.category_code_required'),
            'code.unique' => __('inventory.category_code_unique'),
            'type.required' => __('inventory.category_type_required'),
        ])->validate();

        $status = InventoryCategory::where('id', $id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        if ($status) {
            return response()->json(['message' => __('inventory.category_updated_successfully'), 'status' => true]);
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
        // Check if category has items
        $category = InventoryCategory::find($id);
        if ($category && $category->items()->count() > 0) {
            return response()->json([
                'message' => __('inventory.category_has_items'),
                'status' => false
            ]);
        }

        $status = InventoryCategory::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.category_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
