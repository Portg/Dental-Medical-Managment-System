<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Services\RoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                        ->primary('edit')
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
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
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
}
