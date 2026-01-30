<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Branch;
use App\InventoryBatch;
use App\Patient;
use App\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockOutController extends Controller
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
            $query = StockOut::with(['patient', 'addedBy'])
                ->orderBy('stock_out_date', 'DESC')
                ->orderBy('id', 'DESC');

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Filter by out_type
            if ($request->out_type) {
                $query->where('out_type', $request->out_type);
            }

            // Filter by date range
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('stock_out_date', [$request->start_date, $request->end_date]);
            }

            $data = $query->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('out_type_label', function ($row) {
                    return $row->out_type_label;
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->fullname : '-';
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
                    return '<a href="' . route('stock-outs.show', $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="' . route('stock-outs.edit', $row->id) . '" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
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

        return view('inventory.stock_outs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['branches'] = Branch::orderBy('name')->get();
        $data['stock_out_no'] = StockOut::generateStockOutNo();
        return view('inventory.stock_outs.create')->with($data);
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
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:treatment,department,damage,other',
        ], [
            'stock_out_date.required' => __('inventory.stock_out_date_required'),
            'out_type.required' => __('inventory.out_type_required'),
        ])->validate();

        $stockOut = StockOut::create([
            'stock_out_no' => StockOut::generateStockOutNo(),
            'out_type' => $request->out_type,
            'stock_out_date' => $request->stock_out_date,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'department' => $request->department,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
            'status' => 'draft',
            '_who_added' => Auth::User()->id
        ]);

        if ($stockOut) {
            return response()->json([
                'message' => __('inventory.stock_out_created_successfully'),
                'status' => true,
                'redirect' => route('stock-outs.edit', $stockOut->id)
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
        $data['stockOut'] = StockOut::with(['patient', 'appointment', 'items.inventoryItem', 'addedBy'])->find($id);
        if (!$data['stockOut']) {
            abort(404);
        }
        return view('inventory.stock_outs.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['stockOut'] = StockOut::with(['patient', 'appointment', 'items.inventoryItem'])->find($id);
        if (!$data['stockOut'] || !$data['stockOut']->isDraft()) {
            abort(404);
        }
        $data['branches'] = Branch::orderBy('name')->get();
        return view('inventory.stock_outs.create')->with($data);
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
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_edit_confirmed'), 'status' => false]);
        }

        Validator::make($request->all(), [
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:treatment,department,damage,other',
        ])->validate();

        $status = $stockOut->update([
            'out_type' => $request->out_type,
            'stock_out_date' => $request->stock_out_date,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'department' => $request->department,
            'notes' => $request->notes,
            'branch_id' => $request->branch_id,
        ]);

        if ($status) {
            return response()->json(['message' => __('inventory.stock_out_updated_successfully'), 'status' => true]);
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
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_delete_confirmed'), 'status' => false]);
        }

        $status = $stockOut->delete();
        if ($status) {
            return response()->json(['message' => __('inventory.stock_out_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Confirm the stock out and update inventory.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function confirm($id)
    {
        $stockOut = StockOut::with('items.inventoryItem')->find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_confirm'), 'status' => false]);
        }

        if ($stockOut->items()->count() == 0) {
            return response()->json(['message' => __('inventory.no_items_to_confirm'), 'status' => false]);
        }

        // Check stock availability
        foreach ($stockOut->items as $item) {
            $inventoryItem = $item->inventoryItem;
            if ($inventoryItem->current_stock < $item->qty) {
                return response()->json([
                    'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                    'status' => false
                ]);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($stockOut->items as $item) {
                $inventoryItem = $item->inventoryItem;

                // Deduct from inventory
                $inventoryItem->update([
                    'current_stock' => $inventoryItem->current_stock - $item->qty
                ]);

                // Deduct from batches using FIFO
                $remainingQty = $item->qty;
                $batches = InventoryBatch::where('inventory_item_id', $item->inventory_item_id)
                    ->available()
                    ->fifo()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                    $deductQty = min($batch->qty, $remainingQty);
                    $batch->deductQty($deductQty);
                    $remainingQty -= $deductQty;
                }
            }

            $stockOut->update(['status' => 'confirmed']);

            DB::commit();
            return response()->json(['message' => __('inventory.stock_out_confirmed'), 'status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
        }
    }

    /**
     * Cancel the stock out.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['message' => __('inventory.cannot_cancel'), 'status' => false]);
        }

        $status = $stockOut->update(['status' => 'cancelled']);
        if ($status) {
            return response()->json(['message' => __('inventory.stock_out_cancelled'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
