<?php

namespace App\Http\Controllers;

use App\Services\ExpenseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ExpenseCategoryController extends Controller
{
    private ExpenseCategoryService $service;

    public function __construct(ExpenseCategoryService $service)
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
            $data = $this->service->getList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->AddedBy->othername;
                })
                ->addColumn('expense_account', function ($row) {
                    return $row->ExpenseAccount->name;
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }

        $data['expense_accounts'] = $this->service->getExpenseAccounts();
        return view('expense_categories.index')->with($data);
    }

    public function filterExpenseCategories(Request $request)
    {
        $data = $this->service->filterCategories();
        echo json_encode($data);
    }

    public function searchCategory(Request $request)
    {
        $name = $request->q;

        if ($name) {
            $result = $this->service->searchByName($name);
            return \Response::json($result);
        }
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
            'name' => 'required',
            'expense_account' => 'required'
        ], [
            'name.required' => __('validation.attributes.name') . ' ' . __('validation.required'),
            'expense_account.required' => __('validation.attributes.expense_account') . ' ' . __('validation.required')
        ])->validate();

        $status = $this->service->create($request->all());

        if ($status) {
            return response()->json(['message' => __('expense_categories.added_successfully'), 'status' => true]);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'expense_account' => 'required'
        ], [
            'name.required' => __('validation.attributes.name') . ' ' . __('validation.required'),
            'expense_account.required' => __('validation.attributes.expense_account') . ' ' . __('validation.required')
        ])->validate();

        $status = $this->service->update($id, $request->all());

        if ($status) {
            return response()->json(['message' => __('expense_categories.updated_successfully'), 'status' => true]);
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
            return response()->json(['message' => __('expense_categories.deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
