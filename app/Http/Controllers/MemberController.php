<?php

namespace App\Http\Controllers;

use App\MemberLevel;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'patient_id', 'member_level_id', 'initial_balance', 'payment_method', 'member_expiry',
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

        return view('members.levels.index');
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

        return response()->json($this->memberService->createLevel($request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
        ])));
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

        return response()->json($this->memberService->updateLevel($id, $request->only([
            'name', 'code', 'discount_rate', 'color', 'min_consumption',
            'points_rate', 'benefits', 'sort_order', 'is_default', 'is_active',
        ])));
    }

    /**
     * Delete a member level.
     */
    public function destroyLevel($id)
    {
        return response()->json($this->memberService->deleteLevel($id));
    }
}
