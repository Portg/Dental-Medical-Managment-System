<?php

namespace App\Http\Controllers;

use App\MedicalService;
use App\Services\ServiceConsumableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ServiceConsumableController extends Controller
{
    private ServiceConsumableService $serviceConsumableService;

    public function __construct(ServiceConsumableService $serviceConsumableService)
    {
        $this->serviceConsumableService = $serviceConsumableService;
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
            $data = $this->serviceConsumableService->getList(
                $request->medical_service_id ? (int) $request->medical_service_id : null
            );

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('service_name', function ($row) {
                    return $row->medicalService ? $row->medicalService->name : '-';
                })
                ->addColumn('item_code', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->item_code : '-';
                })
                ->addColumn('item_name', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->name : '-';
                })
                ->addColumn('unit', function ($row) {
                    return $row->inventoryItem ? $row->inventoryItem->unit : '-';
                })
                ->addColumn('is_required_label', function ($row) {
                    if ($row->is_required) {
                        return '<span class="badge badge-success">' . __('inventory.required') . '</span>';
                    }
                    return '<span class="badge badge-secondary">' . __('inventory.optional') . '</span>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['is_required_label', 'deleteBtn'])
                ->make(true);
        }

        $data['services'] = MedicalService::orderBy('name')->get();
        return view('inventory.service_consumables.index')->with($data);
    }

    /**
     * Get consumables for a specific service.
     *
     * @param int $serviceId
     * @return \Illuminate\Http\Response
     */
    public function show($serviceId)
    {
        $consumables = $this->serviceConsumableService->getByService($serviceId);
        return response()->json($consumables);
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
            'medical_service_id' => 'required|exists:medical_services,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'qty' => 'required|numeric|min:0.01',
        ], [
            'medical_service_id.required' => __('inventory.service_required'),
            'inventory_item_id.required' => __('inventory.item_required'),
            'qty.required' => __('inventory.qty_required'),
            'qty.min' => __('inventory.qty_min'),
        ])->validate();

        if ($this->serviceConsumableService->exists($request->medical_service_id, $request->inventory_item_id)) {
            return response()->json([
                'message' => __('inventory.consumable_already_exists'),
                'status' => false,
            ]);
        }

        $consumable = $this->serviceConsumableService->create($request->all());

        if ($consumable) {
            return response()->json(['message' => __('inventory.consumable_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
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
            'qty' => 'required|numeric|min:0.01',
        ])->validate();

        $status = $this->serviceConsumableService->update($id, $request->all());

        if ($status) {
            return response()->json(['message' => __('inventory.consumable_updated_successfully'), 'status' => true]);
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
        $status = $this->serviceConsumableService->delete($id);
        if ($status) {
            return response()->json(['message' => __('inventory.consumable_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
