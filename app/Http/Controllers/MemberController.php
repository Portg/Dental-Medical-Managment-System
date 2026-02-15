<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\MemberLevel;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MemberController extends Controller
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getMemberList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient_name', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('levelBadge', function ($row) {
                    if ($row->level_name) {
                        return '<span class="label" style="background-color:' . $row->level_color . '">' . $row->level_name . '</span>';
                    }
                    return '-';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->member_status == 'Active') $class = 'success';
                    elseif ($row->member_status == 'Expired') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('members.status_' . strtolower($row->member_status)) . '</span>';
                })
                ->addColumn('balance', function ($row) {
                    return number_format($row->member_balance, 2);
                })
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . url('members/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('depositBtn', function ($row) {
                    return '<a href="#" onclick="depositMember(' . $row->id . ')" class="btn btn-success btn-sm">' . __('members.deposit') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editMember(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->rawColumns(['levelBadge', 'statusBadge', 'viewBtn', 'depositBtn', 'editBtn'])
                ->make(true);
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

        return response()->json($this->memberService->registerMember($request->all()));
    }

    /**
     * Update member information.
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'member_level_id' => 'required|exists:member_levels,id',
        ])->validate();

        return response()->json($this->memberService->updateMember($id, $request->all()));
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

        return response()->json($this->memberService->deposit($id, $request->all()));
    }

    /**
     * Get member transactions.
     */
    public function transactions(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->memberService->getTransactions($id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('typeBadge', function ($row) {
                    $class = 'default';
                    if ($row->transaction_type == 'Deposit') $class = 'success';
                    elseif ($row->transaction_type == 'Consumption') $class = 'warning';
                    elseif ($row->transaction_type == 'Refund') $class = 'info';
                    return '<span class="label label-' . $class . '">' . __('members.type_' . strtolower($row->transaction_type)) . '</span>';
                })
                ->addColumn('amountFormatted', function ($row) {
                    $prefix = in_array($row->transaction_type, ['Deposit', 'Refund']) ? '+' : '-';
                    $class = in_array($row->transaction_type, ['Deposit', 'Refund']) ? 'text-success' : 'text-danger';
                    return '<span class="' . $class . '">' . $prefix . number_format($row->amount, 2) . '</span>';
                })
                ->rawColumns(['typeBadge', 'amountFormatted'])
                ->make(true);
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

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('colorBadge', function ($row) {
                    return '<span class="label" style="background-color:' . $row->color . '">' . $row->name . '</span>';
                })
                ->addColumn('discountDisplay', function ($row) {
                    if ($row->discount_rate < 100) {
                        return (100 - $row->discount_rate) . '%';
                    }
                    return __('members.no_discount');
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = $row->is_active ? 'success' : 'default';
                    $text = $row->is_active ? __('common.active') : __('common.inactive');
                    return '<span class="label label-' . $class . '">' . $text . '</span>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editLevel(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteLevel(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['colorBadge', 'statusBadge', 'editBtn', 'deleteBtn'])
                ->make(true);
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

        return response()->json($this->memberService->createLevel($request->all()));
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

        return response()->json($this->memberService->updateLevel($id, $request->all()));
    }

    /**
     * Delete a member level.
     */
    public function destroyLevel($id)
    {
        return response()->json($this->memberService->deleteLevel($id));
    }
}
