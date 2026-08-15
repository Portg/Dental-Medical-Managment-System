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
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return void
     */
    public function edit($id)
    {
        $data = $this->chartOfAccountItemService->findItem((int) $id);
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

        $success = $this->chartOfAccountItemService->updateItem((int) $id, $request->only(['name', 'account_type']));
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
        $id = (int) $id;

        // 不走 messageResponse：它的失败分支会把 message 换成通用的「请重试」，
        // 而这里必须让用户看到删不掉的真实原因。
        if ($this->chartOfAccountItemService->isInUse($id)) {
            return response()->json([
                'message' => __('charts_of_accounts.chart_of_accounts_in_use'),
                'status'  => false,
            ]);
        }

        $success = $this->chartOfAccountItemService->deleteItem($id);

        return FunctionsHelper::messageResponse(__('charts_of_accounts.chart_of_accounts_deleted_successfully'), $success);
    }
}
