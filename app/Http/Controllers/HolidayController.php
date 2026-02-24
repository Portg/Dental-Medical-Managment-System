<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Http\Helper\FunctionsHelper;
use App\Services\HolidayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class HolidayController extends Controller
{
    private HolidayService $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
        $this->middleware('can:manage-holidays');
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
            $data = $this->holidayService->getHolidayList($request->only(['filter_name', 'filter_repeat']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname ?? '-';
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

        $success = $this->holidayService->createHoliday($request->only(['name', 'holiday_date', 'repeat_date']));
        return FunctionsHelper::messageResponse(__('holidays.added_successfully'), $success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $holiday = $this->holidayService->findHoliday($id);
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

        $success = $this->holidayService->updateHoliday($id, $request->only(['name', 'holiday_date', 'repeat_date']));
        return FunctionsHelper::messageResponse(__('holidays.updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $success = $this->holidayService->deleteHoliday($id);
        return FunctionsHelper::messageResponse(__('holidays.deleted_successfully'), $success);
    }
}
