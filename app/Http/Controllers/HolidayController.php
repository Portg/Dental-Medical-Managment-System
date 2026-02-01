<?php

namespace App\Http\Controllers;

use App\Holiday;
use App\Http\Helper\ActionColumnHelper;
use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class HolidayController extends Controller
{
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

            $query = DB::table('holidays')
                ->leftJoin('users', 'users.id', 'holidays._who_added')
                ->whereNull('holidays.deleted_at')
                ->select(['holidays.*', 'users.surname'])
                ->OrderBy('holidays.holiday_date');

            if ($request->filled('filter_name')) {
                $query->where('holidays.name', 'like', '%' . $request->filter_name . '%');
            }
            if ($request->filled('filter_repeat')) {
                $query->where('holidays.repeat_date', $request->filter_repeat);
            }

            $data = $query->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->editColumn('repeat_date', function ($row) {
                    return $row->repeat_date === 'Yes' ? __('common.yes') : __('common.no');
                })
                ->addColumn('action', function ($row) {
                    return ActionColumnHelper::make($row->id)
                        ->primaryIf($row->deleted_at == null, 'edit')
                        ->add('delete')
                        ->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('holidays.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(),
            [
                'name' => 'required',
                'holiday_date' => 'required',
                'repeat_date' => 'required'
            ], [
                'name.required' => __('validation.attributes.holiday_name') . ' ' . __('validation.required'),
                'holiday_date.required' => __('validation.attributes.holiday_date') . ' ' . __('validation.required'),
                'repeat_date.required' => __('validation.attributes.repeat_date') . ' ' . __('validation.required'),
            ])->validate();

        $success = Holiday::create(
            [
                'name' => $request->name,
                'holiday_date' => $request->holiday_date,
                'repeat_date' => $request->repeat_date,
                '_who_added' => Auth::User()->id
            ]);
        return FunctionsHelper::messageResponse(__('holidays.added_successfully'), $success);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Holiday $holiday
     * @return \Illuminate\Http\Response
     */
    public function show(Holiday $holiday)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $holiday = Holiday::where('id', $id)->first();
        return response()->json($holiday);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(),
            [
                'name' => 'required',
                'holiday_date' => 'required',
                'repeat_date' => 'required'
            ], [
                'name.required' => __('validation.attributes.holiday_name') . ' ' . __('validation.required'),
                'holiday_date.required' => __('validation.attributes.holiday_date') . ' ' . __('validation.required'),
                'repeat_date.required' => __('validation.attributes.repeat_date') . ' ' . __('validation.required'),
            ])->validate();

        $success = Holiday::where('id', $id)->update(
            [
                'name' => $request->name,
                'holiday_date' => $request->holiday_date,
                'repeat_date' => $request->repeat_date,
                '_who_added' => Auth::User()->id
            ]);
        return FunctionsHelper::messageResponse(__('holidays.updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $success = Holiday::where('id', $id)->delete();
        return FunctionsHelper::messageResponse(__('holidays.deleted_successfully'), $success);
    }
}
