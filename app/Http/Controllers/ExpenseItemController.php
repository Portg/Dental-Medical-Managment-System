<?php

namespace App\Http\Controllers;

use App\Services\ExpenseItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ExpenseItemController extends Controller
{
    private ExpenseItemService $service;

    public function __construct(ExpenseItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $expense_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $expense_id)
    {
        if ($request->ajax()) {
            $data = $this->service->getListByExpense($expense_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('price', function ($row) {
                    return number_format($row->price);
                })
                ->addColumn('total_amount', function ($row) {
                    return '<span class="bold">' . number_format($row->qty * $row->price) . '</span>';
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editItemRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteItemRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['total_amount', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('expense_items.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'item' => 'required',
            'qty' => 'required',
            'price' => 'required'
        ], [
            'item.required' => __('validation.attributes.item') . ' ' . __('validation.required'),
            'qty.required' => __('validation.attributes.qty') . ' ' . __('validation.required'),
            'price.required' => __('validation.attributes.price') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->service->create($request->all());

        if ($status) {
            return response()->json(['message' => __('expense_items.added_successfully'), 'status' => true]);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->service->find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'item' => 'required',
            'qty' => 'required',
            'price' => 'required'
        ], [
            'item.required' => __('validation.attributes.item') . ' ' . __('validation.required'),
            'qty.required' => __('validation.attributes.qty') . ' ' . __('validation.required'),
            'price.required' => __('validation.attributes.price') . ' ' . __('validation.required'),
        ])->validate();

        $status = $this->service->update($id, $request->all());

        if ($status) {
            return response()->json(['message' => __('expense_items.updated_successfully'), 'status' => true]);
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
        $status = $this->service->delete($id);

        if ($status) {
            return response()->json(['message' => __('expense_items.deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
