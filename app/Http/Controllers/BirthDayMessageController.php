<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\BirthDayMessageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class BirthDayMessageController extends Controller
{
    private BirthDayMessageService $birthDayMessageService;

    public function __construct(BirthDayMessageService $birthDayMessageService)
    {
        $this->birthDayMessageService = $birthDayMessageService;
        $this->middleware('can:manage-settings');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->birthDayMessageService->getList([
                'search' => $request->input('search.value', ''),
            ]);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->deleted_at == null) {
                        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    }
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';

                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('birthday_wishes.index');
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
            'message' => 'required'
        ], [
            'message.required' => __('validation.custom.message.required')
        ])->validate();

        $success = $this->birthDayMessageService->create($request->message);

        return FunctionsHelper::messageResponse(__("messages.message_added_successfully"), $success);
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
     * @param $id
     * @return Response
     */
    public function edit($id)
    {
        return response()->json($this->birthDayMessageService->find($id));
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
            'message' => 'required'
        ], [
            'message.required' => __('validation.custom.message.required')
        ])->validate();

        $success = $this->birthDayMessageService->update($id, $request->message);

        return FunctionsHelper::messageResponse(__("messages.message_updated_successfully"), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return void
     */
    public function destroy($id)
    {
        $success = $this->birthDayMessageService->delete($id);

        return FunctionsHelper::messageResponse(__("messages.message_deleted_successfully"), $success);
    }
}
