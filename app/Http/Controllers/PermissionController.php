<?php

namespace App\Http\Controllers;

use App\Permission;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Application|Factory|Response|View
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Permission::all();

            return Datatables::of($data)
                ->addIndexColumn()
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
        return view('permissions.index');
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
            'name' => ['required', 'unique:permissions,name'],
            'slug' => ['required', 'unique:permissions,slug'],
            'description' => ['nullable'],
            'module' => ['nullable']
        ])->validate();

        $status = Permission::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'module' => $request->module
        ]);

        if ($status) {
            return response()->json(['message' => __('permissions.permission_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse|Response
     */
    public function edit($id)
    {
        $permission = Permission::where('id', $id)->first();
        return response()->json($permission);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => ['required', 'unique:permissions,name,' . $id],
            'slug' => ['required', 'unique:permissions,slug,' . $id],
            'description' => ['nullable'],
            'module' => ['nullable']
        ])->validate();

        $status = Permission::where('id', $id)->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'module' => $request->module
        ]);

        if ($status) {
            return response()->json(['message' => __('permissions.permission_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $status = Permission::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('permissions.permission_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
