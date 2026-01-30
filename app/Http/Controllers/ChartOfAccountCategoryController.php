<?php

namespace App\Http\Controllers;

use App\ChartOfAccountCategory;
use App\AccountingEquation;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ChartOfAccountCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ChartOfAccountCategory::with('accountingEquation')->get();

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

        $data['accounting_equations'] = AccountingEquation::orderBy('sort_by')->get();
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

        $success = ChartOfAccountCategory::create([
            'name' => $request->name,
            'accounting_equation_id' => $request->accounting_equation_id,
            '_who_added' => Auth::User()->id
        ]);

        return FunctionsHelper::messageResponse(__('messages.chart_account_category_added_successfully'), $success);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\ChartOfAccountCategory $chartOfAccountCategory
     * @return \Illuminate\Http\Response
     */
    public function show(ChartOfAccountCategory $chartOfAccountCategory)
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
        $data = ChartOfAccountCategory::with('accountingEquation')
            ->where('id', $id)
            ->first();
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

        $success = ChartOfAccountCategory::where('id', $id)->update([
            'name' => $request->name,
            'accounting_equation_id' => $request->accounting_equation_id,
            '_who_added' => Auth::User()->id
        ]);

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
        $success = ChartOfAccountCategory::where('id', $id)->delete();
        return FunctionsHelper::messageResponse(__('messages.chart_account_category_deleted_successfully'), $success);
    }
}
