<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Services\MenuService;
use App\Services\RoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;

        $this->middleware('can:manage-roles');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = $this->roleService->getAllRoles();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('action', function ($row) {
                    return ActionColumnHelper::make($row->id)
                        ->primary('view', __('common.view'), url("/roles/{$row->id}"))
                        ->add('delete')
                        ->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('roles.index');
    }


    public function filterRoles(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->roleService->searchRoles($name));
        }
        return response()->json([]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required']
        ], [
            'name.required' => __('validation.custom.name.required')
        ])->validate();

        $status = $this->roleService->createRole($request->name);

        if ($status) {
            return response()->json(['message' => __('roles.role_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * 角色详情页（4 Tab）。
     */
    public function show($id)
    {
        $role = $this->roleService->findRoleWithDetails((int) $id);

        if (!$role) {
            abort(404);
        }

        // Tab 2: 权限矩阵
        $permissionsGrouped = $this->roleService->getPermissionsGroupedByModule();
        $rolePermissionIds  = $this->roleService->getRolePermissionIds($role->id);
        $roleTemplates      = $this->roleService->getTemplates();

        // Tab 3: 侧边栏预览
        $sidebarPreview = app(MenuService::class)->getPreviewTreeForRole($role);

        return view('roles.show', compact(
            'role',
            'permissionsGrouped',
            'rolePermissionIds',
            'roleTemplates',
            'sidebarPreview'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function edit($id)
    {
        return response()->json($this->roleService->findRole((int) $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => ['required']
        ], [
            'name.required' => __('validation.custom.name.required')
        ])->validate();

        $status = $this->roleService->updateRole((int) $id, $request->name);

        if ($status) {
            return response()->json(['message' => __('roles.role_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id)
    {
        $status = $this->roleService->deleteRole((int) $id);
        if ($status) {
            return response()->json(['message' => __('roles.role_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }

    /**
     * 批量同步角色权限。
     */
    public function syncPermissions(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permission_ids'   => 'array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $status = $this->roleService->syncPermissions(
            (int) $id,
            $request->input('permission_ids', [])
        );

        if ($status) {
            return response()->json(['message' => __('roles.permissions_synced'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * AJAX: 角色用户列表（DataTable）。
     */
    public function roleUsers(Request $request, $id)
    {
        $users = \App\User::where('role_id', (int) $id)
            ->select('id', 'surname', 'othername', 'email', 'phone_no', 'created_at');

        return Datatables::of($users)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return trim($row->surname . ' ' . $row->othername);
            })
            ->addColumn('phone_number', function ($row) {
                return $row->phone_no ?: '-';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
            })
            ->rawColumns([])
            ->make(true);
    }

    /**
     * 保存侧边栏隐藏覆盖。
     */
    public function saveMenuOverrides(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'hidden_ids'   => 'array',
            'hidden_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $role = $this->roleService->findRole((int) $id);
        if (!$role) {
            return response()->json(['message' => __('roles.not_found'), 'status' => false]);
        }

        $role->hidden_menu_items = $request->input('hidden_ids', []);
        $role->save();

        app(MenuService::class)->clearAllCache();

        return response()->json(['message' => __('roles.sidebar_saved'), 'status' => true]);
    }

    /**
     * AJAX: 获取模板权限 ID 列表。
     */
    public function templatePermissions($slug)
    {
        $ids = $this->roleService->getTemplatePermissionIds($slug);

        if ($ids === null) {
            return response()->json(['message' => __('roles.template_not_found'), 'status' => false]);
        }

        return response()->json(['status' => true, 'data' => $ids]);
    }
}
