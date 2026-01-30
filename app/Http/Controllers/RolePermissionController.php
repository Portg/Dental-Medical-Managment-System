<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use App\RolePermission;
use App\Role;
use App\Permission;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = RolePermission::with(['role', 'permission'])->get();

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

        $data['roles'] = Role::all();
        $data['permissions'] = Permission::all();
        return view('role-permissions.index')->with($data);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'exists:permissions,id']
        ])->validate();

        // 检查是否已存在
        $exists = RolePermission::where('role_id', $request->role_id)
            ->where('permission_id', $request->permission_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => __('role_permissions.permission_already_assigned'), 'status' => false]);
        }

        $status = RolePermission::create([
            'role_id' => $request->role_id,
            'permission_id' => $request->permission_id
        ]);

        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function edit($id)
    {
        $rolePermission = RolePermission::with(['role', 'permission'])->where('id', $id)->first();
        return response()->json($rolePermission);
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'exists:permissions,id']
        ])->validate();

        // 检查是否已存在(排除当前记录)
        $exists = RolePermission::where('role_id', $request->role_id)
            ->where('permission_id', $request->permission_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => __('role_permissions.permission_already_assigned'), 'status' => false]);
        }

        $status = RolePermission::where('id', $id)->update([
            'role_id' => $request->role_id,
            'permission_id' => $request->permission_id
        ]);

        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    public function destroy($id)
    {
        $status = RolePermission::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('role_permissions.role_permission_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
