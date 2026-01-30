<?php

namespace App\Http\Controllers;

use App\AccountingEquation;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccountingEquationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['AccountingEquations'] = AccountingEquation::OrderBy('sort_by')->get();
        return view('charts_of_accounts.index')->with($data);
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
            'sort_by' => 'required|integer'
        ], [
            'name.required' => __('validation.custom.name.required'),
            'sort_by.required' => __('validation.custom.sort_by.required'),
            'sort_by.integer' => __('validation.custom.sort_by.integer')
        ])->validate();

        $success = AccountingEquation::create([
            'name' => $request->name,
            'sort_by' => $request->sort_by,
            'active_tab' => $request->active_tab ?? 'no',
            '_who_added' => Auth::User()->id
        ]);

        return FunctionsHelper::messageResponse(__('messages.accounting_equation_added_successfully'), $success);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\AccountingEquation $accountingEquation
     * @return \Illuminate\Http\Response
     */
    public function show(AccountingEquation $accountingEquation)
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
        $data = AccountingEquation::where('id', $id)->first();
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'sort_by' => 'required|integer'
        ], [
            'name.required' => __('validation.custom.name.required'),
            'sort_by.required' => __('validation.custom.sort_by.required'),
            'sort_by.integer' => __('validation.custom.sort_by.integer')
        ])->validate();

        $success = AccountingEquation::where('id', $id)->update([
            'name' => $request->name,
            'sort_by' => $request->sort_by,
            'active_tab' => $request->active_tab ?? 'no',
            '_who_added' => Auth::User()->id
        ]);

        return FunctionsHelper::messageResponse(__('messages.accounting_equation_updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $success = AccountingEquation::where('id', $id)->delete();
        return FunctionsHelper::messageResponse(__('messages.accounting_equation_deleted_successfully'), $success);
    }
}
