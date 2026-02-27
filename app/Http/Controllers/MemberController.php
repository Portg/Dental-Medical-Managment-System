<?php

namespace App\Http\Controllers;

use App\MemberLevel;
use App\Services\MemberService;
use Illuminate\Http\Request;
use App\MemberSetting;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MemberController extends Controller
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
        $this->middleware('can:manage-members');
    }

    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getMemberList($request->only(['level_id', 'status']));

            return $this->memberService->buildIndexDataTable($data);
        }

        $levels = MemberLevel::active()->ordered()->get();
        $patients = $this->memberService->getNonMembers();

        return view('members.index', compact('levels', 'patients'));
    }

    /**
     * Display the specified member.
     */
    public function show($id)
    {
        $data = $this->memberService->getMemberDetail($id);
        return view('members.show', $data);
    }

    /**
     * Register a patient as a member.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'member_level_id' => 'required|exists:member_levels,id',
        ], [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'member_level_id.required' => __('validation.custom.member_level_id.required'),
        ])->validate();

        return response()->json($this->memberService->registerMember($request->only([
            'patient_id', 'member_level_id', 'initial_balance', 'payment_method', 'member_expiry', 'manual_card_number', 'referred_by',
        ])));
    }

    /**
     * Update member information.
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'member_level_id' => 'required|exists:member_levels,id',
        ])->validate();

        return response()->json($this->memberService->updateMember($id, $request->only([
            'member_level_id', 'member_expiry', 'member_status',
        ])));
    }

    /**
     * Deposit to member balance.
     */
    public function deposit(Request $request, $id)
    {
        Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required',
        ])->validate();

        return response()->json($this->memberService->deposit($id, $request->only([
            'amount', 'payment_method', 'description',
        ])));
    }

    /**
     * Get member transactions.
     */
    public function transactions(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getTransactions($id);

            return $this->memberService->buildTransactionsDataTable($data);
        }
    }

    // ============= Member Levels Management =============

    /**
     * Display member levels.
     */
    public function levels(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getLevelList();

            return $this->memberService->buildLevelsDataTable($data);
        }

        $settings = MemberSetting::getAll();
        $upgradeLevels = MemberLevel::active()->ordered()
            ->where('min_consumption', '>', 0)
            ->orderBy('min_consumption', 'asc')
            ->get();
        return view('members.levels.index', compact('settings', 'upgradeLevels'));
    }

    /**
     * Store a new member level.
     */
    public function storeLevel(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:member_levels,code',
            'discount_rate' => 'required|numeric|min:0|max:100',
        ])->validate();

        $data = $request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
            'opening_fee', 'min_initial_deposit', 'referral_points',
        ]);

        // Parse JSON fields from form
        $data['deposit_bonus_rules'] = $this->parseBonusRules($request);
        $data['payment_method_points_rates'] = $this->parsePointsRates($request);

        return response()->json($this->memberService->createLevel($data));
    }

    /**
     * Get level for editing.
     */
    public function editLevel($id)
    {
        return response()->json($this->memberService->getLevel($id));
    }

    /**
     * Update a member level.
     */
    public function updateLevel(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:member_levels,code,' . $id,
            'discount_rate' => 'required|numeric|min:0|max:100',
        ])->validate();

        $data = $request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
            'opening_fee', 'min_initial_deposit', 'referral_points',
        ]);

        $data['deposit_bonus_rules'] = $this->parseBonusRules($request);
        $data['payment_method_points_rates'] = $this->parsePointsRates($request);

        return response()->json($this->memberService->updateLevel($id, $data));
    }

    /**
     * Delete a member level.
     */
    public function destroyLevel($id)
    {
        return response()->json($this->memberService->deleteLevel($id));
    }

    /**
     * Refund from member balance.
     */
    public function refund(Request $request, $id)
    {
        Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required',
        ])->validate();

        return response()->json($this->memberService->refund($id, $request->only([
            'amount', 'payment_method', 'description',
        ])));
    }

    /**
     * Exchange member points for balance.
     */
    public function exchangePoints(Request $request, $id)
    {
        Validator::make($request->all(), [
            'points' => 'required|integer|min:1',
        ])->validate();

        return response()->json($this->memberService->exchangePoints($id, (int) $request->input('points')));
    }

    // ============= Shared Card Holders =============

    /**
     * Get shared card holders for a member.
     */
    public function sharedHolders(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getSharedHolders($id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('removeBtn', function ($row) {
                    return '<button class="btn btn-xs btn-danger" onclick="removeSharedHolder(' . $row->id . ')"><i class="fa fa-times"></i> ' . __('common.delete') . '</button>';
                })
                ->rawColumns(['removeBtn'])
                ->make(true);
        }
    }

    /**
     * Add a shared card holder.
     */
    public function addSharedHolder(Request $request, $id)
    {
        Validator::make($request->all(), [
            'shared_patient_id' => 'required|exists:patients,id',
            'relationship' => 'required|string|max:50',
        ])->validate();

        return response()->json($this->memberService->addSharedHolder(
            (int) $id,
            (int) $request->input('shared_patient_id'),
            $request->input('relationship')
        ));
    }

    /**
     * Remove a shared card holder.
     */
    public function removeSharedHolder($holderId)
    {
        return response()->json($this->memberService->removeSharedHolder((int) $holderId));
    }

    /**
     * Get audit logs for a member.
     */
    public function auditLogs(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getAuditLogs($id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('actionBadge', function ($row) {
                    $key = 'members.action_' . $row->action;
                    return '<span class="label label-default">' . __($key) . '</span>';
                })
                ->rawColumns(['actionBadge'])
                ->make(true);
        }
    }

    // ============= Password & Print =============

    /**
     * Set member password.
     */
    public function setPassword(Request $request, $id)
    {
        Validator::make($request->all(), [
            'password' => 'required|string|min:4|confirmed',
        ])->validate();

        return response()->json($this->memberService->setPassword((int) $id, $request->input('password')));
    }

    /**
     * Print member card.
     */
    public function printCard($id)
    {
        $data = $this->memberService->getMemberDetail($id);
        return view('members.print', $data);
    }

    // ============= Member Settings =============

    /**
     * Display member settings page.
     */
    public function settings()
    {
        return redirect('member-levels#tab_card_number');
    }

    /**
     * Update member settings.
     */
    public function updateSettings(Request $request)
    {
        $keys = [
            'points_enabled', 'points_expiry_days', 'card_number_mode',
            'referral_bonus_enabled', 'points_exchange_rate', 'points_exchange_enabled',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                MemberSetting::set($key, $request->input($key));
            }
        }

        return response()->json(['message' => __('members.settings_saved'), 'status' => true]);
    }

    // ============= Private helpers =============

    /**
     * Parse deposit bonus rules from form input arrays.
     */
    private function parseBonusRules(Request $request): ?array
    {
        $minAmounts = $request->input('bonus_min_amount', []);
        $bonusAmounts = $request->input('bonus_amount', []);

        if (empty($minAmounts)) {
            return null;
        }

        $rules = [];
        foreach ($minAmounts as $i => $minAmount) {
            if (!empty($minAmount) && isset($bonusAmounts[$i]) && $bonusAmounts[$i] !== '') {
                $rules[] = [
                    'min_amount' => (float) $minAmount,
                    'bonus'      => (float) $bonusAmounts[$i],
                ];
            }
        }

        return empty($rules) ? null : $rules;
    }

    /**
     * Parse payment method points rates from form input.
     */
    private function parsePointsRates(Request $request): ?array
    {
        $methods = $request->input('pm_points_method', []);
        $rates = $request->input('pm_points_rate', []);

        if (empty($methods)) {
            return null;
        }

        $result = [];
        foreach ($methods as $i => $method) {
            if (!empty($method) && isset($rates[$i]) && $rates[$i] !== '') {
                $result[$method] = (float) $rates[$i];
            }
        }

        return empty($result) ? null : $result;
    }
}
