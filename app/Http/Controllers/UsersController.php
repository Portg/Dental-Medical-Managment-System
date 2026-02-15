<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class UsersController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
            $data = $this->userService->getUserList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('full_name', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('is_doctor', function ($row) {
                    if ($row->is_doctor == "Yes") {
                        return '<span class="label label-sm label-success">' . $row->is_doctor . '</span>';
                    } else {
                        return '<span class="label label-sm label-default">' . $row->is_doctor . '</span>';
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
                ->rawColumns(['is_doctor', 'editBtn', 'deleteBtn'])
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
                'password' => 'min:6 | required_with:password_confirmation',
                'password_confirmation' => 'min:6 | same:password_confirmation'
            ])->validate();
        } else {
            Validator::make($request->all(), [
                'surname' => ['required'],
                'othername' => ['required'],
                'email' => 'required',
                'password' => 'min:6 | required_with:password_confirmation',
                'password_confirmation' => 'min:6 | same:password_confirmation'
            ])->validate();
        }

        $nameParts = $this->userService->parseNameParts($request->all());
        $status = $this->userService->createUser($nameParts, $request->all());
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
        return response()->json($this->userService->getUserForEdit($id));
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
        $nameParts = $this->userService->parseNameParts($request->all());
        $status = $this->userService->updateUser($id, $nameParts, $request->all());
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
        $status = $this->userService->deleteUser($id);
        return FunctionsHelper::messageResponse(__('messages.user_deleted_successfully'), $status);
    }


}
