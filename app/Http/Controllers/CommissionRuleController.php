<?php

namespace App\Http\Controllers;

use App\CommissionRule;
use App\MedicalService;
use App\Branch;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CommissionRuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('commission_rules')
                ->leftJoin('medical_services', 'medical_services.id', 'commission_rules.medical_service_id')
                ->leftJoin('branches', 'branches.id', 'commission_rules.branch_id')
                ->leftJoin('users', 'users.id', 'commission_rules._who_added')
                ->whereNull('commission_rules.deleted_at')
                ->select([
                    'commission_rules.*',
                    'medical_services.name as service_name',
                    'branches.name as branch_name',
                    'users.surname as added_by'
                ])
                ->orderBy('commission_rules.id', 'desc')
                ->get();

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

        $services = MedicalService::whereNull('deleted_at')->get();
        $branches = Branch::whereNull('deleted_at')->get();

        return view('commission_rules.index', compact('services', 'branches'));
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

        $data = [
            'rule_name' => $request->rule_name,
            'commission_mode' => $request->commission_mode,
            'target_service_type' => $request->target_service_type,
            'medical_service_id' => $request->medical_service_id ?: null,
            'base_commission_rate' => $request->base_commission_rate ?: 0,
            'tier1_threshold' => $request->tier1_threshold ?: null,
            'tier1_rate' => $request->tier1_rate ?: null,
            'tier2_threshold' => $request->tier2_threshold ?: null,
            'tier2_rate' => $request->tier2_rate ?: null,
            'tier3_threshold' => $request->tier3_threshold ?: null,
            'tier3_rate' => $request->tier3_rate ?: null,
            'bonus_amount' => $request->bonus_amount ?: 0,
            'is_active' => $request->is_active ? true : false,
            'branch_id' => $request->branch_id ?: null,
            '_who_added' => Auth::user()->id,
        ];

        $success = CommissionRule::create($data);

        return FunctionsHelper::messageResponse(__('commission_rules.added_successfully'), $success);
    }

    public function edit($id)
    {
        $rule = CommissionRule::where('id', $id)->first();
        return response()->json($rule);
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

        $success = CommissionRule::where('id', $id)->update([
            'rule_name' => $request->rule_name,
            'commission_mode' => $request->commission_mode,
            'target_service_type' => $request->target_service_type,
            'medical_service_id' => $request->medical_service_id ?: null,
            'base_commission_rate' => $request->base_commission_rate ?: 0,
            'tier1_threshold' => $request->tier1_threshold ?: null,
            'tier1_rate' => $request->tier1_rate ?: null,
            'tier2_threshold' => $request->tier2_threshold ?: null,
            'tier2_rate' => $request->tier2_rate ?: null,
            'tier3_threshold' => $request->tier3_threshold ?: null,
            'tier3_rate' => $request->tier3_rate ?: null,
            'bonus_amount' => $request->bonus_amount ?: 0,
            'is_active' => $request->is_active ? true : false,
            'branch_id' => $request->branch_id ?: null,
        ]);

        return FunctionsHelper::messageResponse(__('commission_rules.updated_successfully'), $success);
    }

    public function destroy($id)
    {
        $success = CommissionRule::where('id', $id)->delete();
        return FunctionsHelper::messageResponse(__('commission_rules.deleted_successfully'), $success);
    }

    /**
     * Calculate commission for given service and revenue
     */
    public function calculate(Request $request)
    {
        $serviceId = $request->service_id;
        $revenue = $request->revenue;

        $rule = CommissionRule::where('medical_service_id', $serviceId)
            ->orWhere('target_service_type', $request->service_type)
            ->active()
            ->first();

        if (!$rule) {
            return response()->json(['commission' => 0, 'message' => __('commission_rules.no_rule_found')]);
        }

        $commission = $rule->calculateCommission($revenue);

        return response()->json([
            'commission' => round($commission, 2),
            'rule_name' => $rule->rule_name,
            'mode' => $rule->commission_mode
        ]);
    }
}
