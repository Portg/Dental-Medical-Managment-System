<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\ChartOfAccountItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountItemController extends Controller
{
    private ChartOfAccountItemService $chartOfAccountItemService;

    public function __construct(ChartOfAccountItemService $chartOfAccountItemService)
    {
        $this->chartOfAccountItemService = $chartOfAccountItemService;
        $this->middleware('can:manage-accounting');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'account_type' => 'required'
        ])->validate();

        $success = $this->chartOfAccountItemService->createItem($request->only(['name', 'account_type']));
        if ($success) {
            return FunctionsHelper::messageResponse(__('charts_of_accounts.chart_of_accounts_added_successfully'), $success);
        }
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
     * @return void
     */
    public function edit($id)
    {
        $data = $this->chartOfAccountItemService->findItem($id);
        return response()->json($data);
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
            'name' => 'required',
            'account_type' => 'required'
        ])->validate();

        $success = $this->chartOfAccountItemService->updateItem($id, $request->only(['name', 'account_type']));
        if ($success) {
            return FunctionsHelper::messageResponse(__('charts_of_accounts.chart_of_accounts_updated_successfully'), $success);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
