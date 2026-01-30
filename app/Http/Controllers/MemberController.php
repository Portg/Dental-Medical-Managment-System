<?php

namespace App\Http\Controllers;

use App\MemberLevel;
use App\MemberTransaction;
use App\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('patients')
                ->leftJoin('member_levels', 'member_levels.id', 'patients.member_level_id')
                ->whereNull('patients.deleted_at')
                ->where('patients.member_status', '!=', 'Inactive')
                ->orderBy('patients.member_since', 'desc')
                ->select(
                    'patients.*',
                    'member_levels.name as level_name',
                    'member_levels.color as level_color',
                    'member_levels.discount_rate'
                );

            // Filter by level
            if ($request->has('level_id') && $request->level_id) {
                $query->where('patients.member_level_id', $request->level_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('patients.member_status', $request->status);
            }

            $data = $query->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient_name', function ($row) {
                    return $row->surname . ' ' . $row->othername;
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
        $patients = Patient::whereNull('deleted_at')
            ->where(function($q) {
                $q->whereNull('member_status')
                  ->orWhere('member_status', 'Inactive');
            })
            ->orderBy('surname')
            ->get();

        return view('members.index', compact('levels', 'patients'));
    }

    /**
     * Display the specified member.
     */
    public function show($id)
    {
        $patient = Patient::with(['memberLevel', 'memberTransactions' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }])->findOrFail($id);

        $levels = MemberLevel::active()->ordered()->get();

        return view('members.show', compact('patient', 'levels'));
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

        $patient = Patient::findOrFail($request->patient_id);

        // Check if already a member
        if ($patient->member_status === 'Active') {
            return response()->json(['message' => __('members.already_member'), 'status' => false]);
        }

        $patient->update([
            'member_no' => Patient::generateMemberNo(),
            'member_level_id' => $request->member_level_id,
            'member_balance' => $request->initial_balance ?? 0,
            'member_points' => 0,
            'total_consumption' => 0,
            'member_since' => now(),
            'member_expiry' => $request->member_expiry,
            'member_status' => 'Active',
        ]);

        // Record initial deposit if any
        if ($request->initial_balance > 0) {
            MemberTransaction::create([
                'transaction_no' => MemberTransaction::generateTransactionNo(),
                'transaction_type' => 'Deposit',
                'amount' => $request->initial_balance,
                'balance_before' => 0,
                'balance_after' => $request->initial_balance,
                'payment_method' => $request->payment_method ?? 'Cash',
                'description' => __('members.initial_deposit'),
                'patient_id' => $patient->id,
                '_who_added' => Auth::User()->id,
            ]);
        }

        return response()->json(['message' => __('members.member_registered_successfully'), 'status' => true]);
    }

    /**
     * Update member information.
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'member_level_id' => 'required|exists:member_levels,id',
        ])->validate();

        $patient = Patient::findOrFail($id);

        $patient->update([
            'member_level_id' => $request->member_level_id,
            'member_expiry' => $request->member_expiry,
            'member_status' => $request->member_status,
        ]);

        return response()->json(['message' => __('members.member_updated_successfully'), 'status' => true]);
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

        $patient = Patient::findOrFail($id);

        if ($patient->member_status !== 'Active') {
            return response()->json(['message' => __('members.not_active_member'), 'status' => false]);
        }

        $balanceBefore = $patient->member_balance;
        $balanceAfter = $balanceBefore + $request->amount;

        // Update balance
        $patient->update(['member_balance' => $balanceAfter]);

        // Record transaction
        MemberTransaction::create([
            'transaction_no' => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Deposit',
            'amount' => $request->amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'payment_method' => $request->payment_method,
            'description' => $request->description ?? __('members.balance_deposit'),
            'patient_id' => $patient->id,
            '_who_added' => Auth::User()->id,
        ]);

        return response()->json([
            'message' => __('members.deposit_successful'),
            'status' => true,
            'new_balance' => $balanceAfter
        ]);
    }

    /**
     * Get member transactions.
     */
    public function transactions(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = DB::table('member_transactions')
                ->leftJoin('users as added_by', 'added_by.id', 'member_transactions._who_added')
                ->whereNull('member_transactions.deleted_at')
                ->where('member_transactions.patient_id', $id)
                ->orderBy('member_transactions.created_at', 'desc')
                ->select(
                    'member_transactions.*',
                    DB::raw("CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
                )
                ->get();

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
            $data = MemberLevel::whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get();

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

        MemberLevel::create([
            'name' => $request->name,
            'code' => $request->code,
            'color' => $request->color ?? '#999999',
            'discount_rate' => $request->discount_rate,
            'min_consumption' => $request->min_consumption ?? 0,
            'points_rate' => $request->points_rate ?? 1,
            'benefits' => $request->benefits,
            'sort_order' => $request->sort_order ?? 0,
            'is_default' => $request->is_default ?? false,
            'is_active' => $request->is_active ?? true,
            '_who_added' => Auth::User()->id,
        ]);

        return response()->json(['message' => __('members.level_created_successfully'), 'status' => true]);
    }

    /**
     * Get level for editing.
     */
    public function editLevel($id)
    {
        $level = MemberLevel::findOrFail($id);
        return response()->json($level);
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

        MemberLevel::where('id', $id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'color' => $request->color ?? '#999999',
            'discount_rate' => $request->discount_rate,
            'min_consumption' => $request->min_consumption ?? 0,
            'points_rate' => $request->points_rate ?? 1,
            'benefits' => $request->benefits,
            'sort_order' => $request->sort_order ?? 0,
            'is_default' => $request->is_default ?? false,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json(['message' => __('members.level_updated_successfully'), 'status' => true]);
    }

    /**
     * Delete a member level.
     */
    public function destroyLevel($id)
    {
        // Check if any members have this level
        $count = Patient::where('member_level_id', $id)->count();
        if ($count > 0) {
            return response()->json([
                'message' => __('members.level_has_members', ['count' => $count]),
                'status' => false
            ]);
        }

        MemberLevel::where('id', $id)->delete();

        return response()->json(['message' => __('members.level_deleted_successfully'), 'status' => true]);
    }
}
