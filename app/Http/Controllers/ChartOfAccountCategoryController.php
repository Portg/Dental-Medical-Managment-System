<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\ChartOfAccountCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ChartOfAccountCategoryController extends Controller
{
    private ChartOfAccountCategoryService $chartOfAccountCategoryService;

    public function __construct(ChartOfAccountCategoryService $chartOfAccountCategoryService)
    {
        $this->chartOfAccountCategoryService = $chartOfAccountCategoryService;
        $this->middleware('can:manage-accounting');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->chartOfAccountCategoryService->getAllCategories();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('equation_name', function ($row) {
                    return $row->accountingEquation ? $row->accountingEquation->name : '';
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

        $data['accounting_equations'] = $this->chartOfAccountCategoryService->getAccountingEquations();
        return view('chart_of_account_categories.index')->with($data);
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
            'accounting_equation_id' => 'required'
        ], [
            'name.required' => __('validation.custom.name.required'),
            'accounting_equation_id.required' => __('validation.custom.accounting_equation_id.required')
        ])->validate();

        $success = $this->chartOfAccountCategoryService->createCategory($request->only(['name', 'accounting_equation_id']));

        return FunctionsHelper::messageResponse(__('messages.chart_account_category_added_successfully'), $success);
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
     * @param $id
     * @return Response
     */
    public function edit($id)
    {
        $data = $this->chartOfAccountCategoryService->findCategory((int) $id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'accounting_equation_id' => 'required'
        ], [
            'name.required' => __('validation.custom.name.required'),
            'accounting_equation_id.required' => __('validation.custom.accounting_equation_id.required')
        ])->validate();

        $success = $this->chartOfAccountCategoryService->updateCategory((int) $id, $request->only(['name', 'accounting_equation_id']));

        return FunctionsHelper::messageResponse(__('messages.chart_account_category_updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $success = $this->chartOfAccountCategoryService->deleteCategory((int) $id);
        return FunctionsHelper::messageResponse(__('messages.chart_account_category_deleted_successfully'), $success);
    }
}
