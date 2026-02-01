<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Role;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
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

            $data = Role::all();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
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
            $search = $name;
            $data =
                Role::where('name', 'LIKE', "%$search%")->get();

            $formatted_tags = [];
            foreach ($data as $tag) {
                $formatted_tags[] = ['id' => $tag->id, 'text' => $tag->name];
            }
            return \Response::json($formatted_tags);
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
        $status = Role::create([
            'name' => $request->name
        ]);
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
        $role = Role::where('id', $id)->first();
        return response()->json($role);
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
        $status = Role::where('id', $id)->update([
            'name' => $request->name
        ]);
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
        $status = Role::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('roles.role_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }
}
