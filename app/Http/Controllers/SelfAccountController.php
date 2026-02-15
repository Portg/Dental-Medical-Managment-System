<?php

namespace App\Http\Controllers;

use App\Services\SelfAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SelfAccountController extends Controller
{
    private SelfAccountService $service;

    public function __construct(SelfAccountService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $search = !empty($_GET['search']) ? $request->get('search') : null;
            $data = $this->service->getAccountList($search);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('account_holder', function ($row) {
                    return ' <a href="' . url('self-accounts/' . $row->id) . '"  >' . $row->account_holder . '</a>';
                })
                ->addColumn('account_balance', function ($row) {
                    $account_balance = $this->service->getAccountBalance($row->id);
                    return '<span class="text-primary">' . number_format($account_balance) . '</span>';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->addColumn('status', function ($row) {
                    if ($row->deleted_at != null) {
                        return '<span class="text-danger">' . __('common.inactive') . '</span>';
                    } else {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
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
                ->rawColumns(['account_holder', 'status', 'account_balance', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('self_accounts.index');
    }

    public function filterAccounts(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->service->searchAccounts($name));
        }

        return \Response::json([]);
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
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required',
        ])->validate();

        $status = $this->service->createAccount($request->all());

        if ($status) {
            return response()->json(['message' => __('financial.self_account_created_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        $data['account_info'] = $this->service->find($id);
        return view('self_accounts.preview_account')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Response
     */
    public function edit($id)
    {
        $self_account = $this->service->find($id);
        return response()->json($self_account);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required',
        ])->validate();

        $status = $this->service->updateAccount($id, $request->all());

        if ($status) {
            return response()->json(['message' => __('financial.self_account_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Response
     */
    public function destroy($id)
    {
        $status = $this->service->deleteAccount($id);
        if ($status) {
            return response()->json(['message' => __('financial.self_account_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
