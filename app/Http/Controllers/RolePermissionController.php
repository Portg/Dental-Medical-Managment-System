<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class RolePermissionController extends Controller
{
    private RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;

        $this->middleware('can:manage-role-permissions');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->rolePermissionService->getAllRolePermissions();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('role_name', function ($row) {
                    return $row->role ? $row->role->name : '';
                })
                ->addColumn('permission_name', function ($row) {
                    return $row->permission ? $row->permission->name : '';
                })
                ->addColumn('permission_slug', function ($row) {
                    return $row->permission ? $row->permission->slug : '';
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }

        $data['roles'] = $this->rolePermissionService->getAllRoles();
        $data['permissions'] = $this->rolePermissionService->getAllPermissions();
        return view('role-permissions.index')->with($data);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'exists:permissions,id']
        ])->validate();

        // 检查是否已存在
        if ($this->rolePermissionService->exists((int) $request->role_id, (int) $request->permission_id)) {
            return response()->json(['message' => __('role_permissions.permission_already_assigned'), 'status' => false]);
        }

        $status = $this->rolePermissionService->createRolePermission((int) $request->role_id, (int) $request->permission_id);

        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function edit($id)
    {
        return response()->json($this->rolePermissionService->findRolePermission((int) $id));
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'exists:permissions,id']
        ])->validate();

        // 检查是否已存在(排除当前记录)
        if ($this->rolePermissionService->exists((int) $request->role_id, (int) $request->permission_id, (int) $id)) {
            return response()->json(['message' => __('role_permissions.permission_already_assigned'), 'status' => false]);
        }

        $status = $this->rolePermissionService->updateRolePermission((int) $id, (int) $request->role_id, (int) $request->permission_id);

        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function destroy($id)
    {
        $status = $this->rolePermissionService->deleteRolePermission((int) $id);
        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
