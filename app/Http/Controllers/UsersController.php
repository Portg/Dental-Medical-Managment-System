<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class UsersController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        $this->middleware('can:view-users')->only(['index', 'show', 'filterDoctor', 'filterEmployees']);
        $this->middleware('can:create-users')->only(['create', 'store']);
        $this->middleware('can:edit-users')->only(['edit', 'update', 'changeStatus']);
        $this->middleware('can:delete-users')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->userService->getUserList([
                'search' => $request->input('search.value', ''),
            ]);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('full_name', function ($row) {
                    return e(NameHelper::join($row->surname, $row->othername));
                })
                ->addColumn('status_label', function ($row) {
                    if ($row->status === 'active') {
                        return '<span class="label label-sm label-success">' . __('users.status_active') . '</span>';
                    }
                    return '<span class="label label-sm label-danger">' . __('users.status_resigned') . '</span>';
                })
                ->addColumn('is_doctor', function ($row) {
                    if ($row->is_doctor) {
                        return '<span class="label label-sm label-success">' . __('common.yes') . '</span>';
                    } else {
                        return '<span class="label label-sm label-default">' . __('common.no') . '</span>';
                    }
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->deleted_at == null) {
                        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    }
                })
                ->addColumn('deleteBtn', function ($row) {

                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';

                })
                ->rawColumns(['status_label', 'is_doctor', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('users.index');
    }

    public function filterDoctor(Request $request)
    {
        $formatted = $this->userService->searchDoctors($request->q);
        return \Response::json($formatted);
    }

    public function filterEmployees(Request $request)
    {
        $name = $request->q;
        if ($name) {
            $formatted = $this->userService->searchEmployees($name);
            return \Response::json($formatted);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Locale-adaptive validation
        if ($request->filled('full_name')) {
            Validator::make($request->all(), [
                'full_name' => ['required', 'min:2'],
                'email' => 'required',
                'password' => ['required_with:password_confirmation', new \App\Rules\StrongPassword],
                'password_confirmation' => 'same:password'
            ])->validate();
        } else {
            Validator::make($request->all(), [
                'surname' => ['required'],
                'othername' => ['required'],
                'email' => 'required',
                'password' => ['required_with:password_confirmation', new \App\Rules\StrongPassword],
                'password_confirmation' => 'same:password'
            ])->validate();
        }

        $userFields = $request->only([
            'full_name', 'surname', 'othername', 'username', 'email', 'password',
            'phone_no', 'alternative_no', 'nin', 'role_id', 'branch_id', 'is_doctor',
        ]);
        $nameParts = $this->userService->parseNameParts($userFields);
        $status = $this->userService->createUser($nameParts, $userFields);
        return FunctionsHelper::messageResponse(__('messages.user_registered_successfully'), $status);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->userService->getUserForEdit((int) $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $userFields = $request->only([
            'full_name', 'surname', 'othername', 'username', 'email',
            'phone_no', 'alternative_no', 'nin', 'role_id', 'branch_id', 'is_doctor',
        ]);
        $nameParts = $this->userService->parseNameParts($userFields);
        $status = $this->userService->updateUser((int) $id, $nameParts, $userFields);
        return FunctionsHelper::messageResponse(__('messages.user_updated_successfully'), $status);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->userService->deleteUser((int) $id);
        return FunctionsHelper::messageResponse(__('messages.user_deleted_successfully'), $status);
    }

    /**
     * Change user status (AG-027/AG-031).
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,resigned',
            'new_password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 0]);
        }

        $newStatus = $request->input('status');
        $newPassword = $request->input('new_password');

        // AG-027: 管理员不能将自己离职
        if ((int) $id === Auth::id() && $newStatus === 'resigned') {
            return response()->json(['message' => __('users.cannot_resign_yourself'), 'status' => 0]);
        }

        $user = \App\User::find($id);
        if (!$user) {
            return response()->json(['message' => __('messages.not_found'), 'status' => 0]);
        }

        // AG-070: 禁止离职最后一个在职超管
        if ($newStatus === 'resigned'
            && $user->UserRole
            && $user->UserRole->slug === 'super-admin'
            && $user->status === \App\User::STATUS_ACTIVE
        ) {
            $activeSuperAdminCount = \App\User::whereHas('UserRole', fn ($q) => $q->where('slug', 'super-admin'))
                ->where('status', \App\User::STATUS_ACTIVE)
                ->whereNull('deleted_at')
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return response()->json(['message' => __('users.cannot_resign_last_super_admin'), 'status' => 0]);
            }
        }

        // AG-031: 复职必须提供新密码
        if ($newStatus === 'active' && $user->status === 'resigned' && empty($newPassword)) {
            return response()->json([
                'message' => __('users.password_required_for_reactivation'),
                'status' => 0,
            ]);
        }

        $result = $this->userService->changeUserStatus((int) $id, $newStatus, $newPassword);

        $message = $newStatus === 'resigned'
            ? __('users.user_resigned_successfully')
            : __('users.user_reactivated_successfully');

        return FunctionsHelper::messageResponse($message, $result);
    }
}
