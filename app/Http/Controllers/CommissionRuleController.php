<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\CommissionRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CommissionRuleController extends Controller
{
    private CommissionRuleService $service;

    public function __construct(CommissionRuleService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-doctor-claims');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('mode_label', function ($row) {
                    return __('commission_rules.mode_' . $row->commission_mode);
                })
                ->addColumn('rate_display', function ($row) {
                    switch ($row->commission_mode) {
                        case 'fixed_percentage':
                            return $row->base_commission_rate . '%';
                        case 'fixed_amount':
                            return number_format($row->bonus_amount, 2);
                        case 'tiered':
                        case 'mixed':
                            return __('commission_rules.tiered_rate');
                        default:
                            return '-';
                    }
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="label label-success">' . __('common.active') . '</span>';
                    }
                    return '<span class="label label-default">' . __('common.inactive') . '</span>';
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

        $viewData = $this->service->getViewData();

        return view('commission_rules.index', $viewData);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'rule_name' => 'required|max:100',
            'commission_mode' => 'required|in:fixed_percentage,tiered,fixed_amount,mixed',
        ], [
            'rule_name.required' => __('commission_rules.name_required'),
            'commission_mode.required' => __('commission_rules.mode_required'),
        ])->validate();

        $success = $this->service->create($request->only(['rule_name', 'commission_mode']));

        return FunctionsHelper::messageResponse(__('commission_rules.added_successfully'), $success);
    }

    public function edit($id)
    {
        return response()->json($this->service->find((int) $id));
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'rule_name' => 'required|max:100',
            'commission_mode' => 'required|in:fixed_percentage,tiered,fixed_amount,mixed',
        ], [
            'rule_name.required' => __('commission_rules.name_required'),
            'commission_mode.required' => __('commission_rules.mode_required'),
        ])->validate();

        $success = $this->service->update((int) $id, $request->only(['rule_name', 'commission_mode']));

        return FunctionsHelper::messageResponse(__('commission_rules.updated_successfully'), $success);
    }

    public function destroy($id)
    {
        $success = $this->service->delete((int) $id);
        return FunctionsHelper::messageResponse(__('commission_rules.deleted_successfully'), $success);
    }

    /**
     * Calculate commission for given service and revenue
     */
    public function calculate(Request $request)
    {
        $result = $this->service->calculateCommission(
            $request->service_id,
            $request->service_type,
            $request->revenue
        );

        return response()->json($result);
    }
}
