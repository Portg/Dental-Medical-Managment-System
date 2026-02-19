<?php

namespace App\Http\Controllers;

use App\Services\ClaimRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ClaimRateController extends Controller
{
    private ClaimRateService $claimRateService;

    public function __construct(ClaimRateService $claimRateService)
    {
        $this->claimRateService = $claimRateService;
        $this->middleware('can:manage-doctor-claims');
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

            $data = $this->claimRateService->getClaimRateList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('action', function ($row) {
                    $action = " <a href=\"#\" onclick=\"newClaim('" . $row->doctor_id . "','" . $row->othername .
                        "')\">" . __('claim_rates.new_claim_rate') . "</a>";
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')"> ' . __('common.edit') . ' </a>
                            </li>
                              <li>
                                <a href="#" onclick="deleteRecord(' . $row->id . ')"> ' . __('common.delete') . ' </a>
                            </li>
                             <li>
                              ' . $action . '
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['amount', 'action'])
                ->make(true);
        }
        return view('claim_rates.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
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
        Validator::make($request->all(), [
            'doctor_id' => 'required',
            'cash_rate' => 'required',
            'insurance_rate' => 'required',
            'insurance_rate' => 'required'
        ])->validate();

        $status = $this->claimRateService->createClaimRate($request->only(['doctor_id', 'cash_rate', 'insurance_rate']));
        if ($status) {
            return response()->json(['message' => __('claim_rates.claim_rates_added_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'),
            'status' => false]);
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
        return response()->json($this->claimRateService->getClaimRateForEdit($id));
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
        Validator::make($request->all(), [
            'doctor_id' => 'required',
            'cash_rate' => 'required',
            'insurance_rate' => 'required',
        ])->validate();

        $status = $this->claimRateService->updateClaimRate($id, $request->only(['doctor_id', 'cash_rate', 'insurance_rate']));
        if ($status) {
            return response()->json(['message' => __('claim_rates.claim_rates_updated_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'),
            'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->claimRateService->deleteClaimRate($id);
        if ($status) {
            return response()->json(['message' => __('claim_rates.claim_rates_deleted_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'),
            'status' => false]);
    }
}
