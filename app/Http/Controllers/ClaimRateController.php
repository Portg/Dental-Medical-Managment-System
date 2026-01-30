<?php

namespace App\Http\Controllers;

use App\ClaimRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ClaimRateController extends Controller
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

            $data = DB::table('claim_rates')
                ->join('users', 'users.id', 'claim_rates.doctor_id')
                ->whereNull('claim_rates.deleted_at')
                ->where('claim_rates.status', 'active')
                ->select('claim_rates.*', 'users.surname', 'users.othername')
                ->groupBy('claim_rates.doctor_id')
                ->orderBy('claim_rates.updated_at', 'desc')
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
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
        //check if there is a previous rate for this doctor and de-active it
        $has_claim = ClaimRate::where('doctor_id', $request->doctor_id)->first();
        if ($has_claim != null) {
            // de-active the old rate
            ClaimRate::where('doctor_id', $request->doctor_id)->update(['status' => 'deactivated']);
        }
        //now insert the new rate
        $status = ClaimRate::create([
            'doctor_id' => $request->doctor_id,
            'cash_rate' => $request->cash_rate,
            'insurance_rate' => $request->insurance_rate,
            '_who_added' => Auth::User()->id
        ]);
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
     * @param \App\ClaimRate $claimRate
     * @return \Illuminate\Http\Response
     */
    public function show(ClaimRate $claimRate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\ClaimRate $claimRate
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $rate = DB::table('claim_rates')
            ->join('users', 'users.id', 'claim_rates.doctor_id')
            ->where('claim_rates.id', $id)
            ->select('claim_rates.*', 'users.surname', 'users.othername')
            ->first();
        return response()->json($rate);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\ClaimRate $claimRate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'doctor_id' => 'required',
            'cash_rate' => 'required',
            'insurance_rate' => 'required',
        ])->validate();

        $status = ClaimRate::where('id', $id)->update([
            'doctor_id' => $request->doctor_id,
            'cash_rate' => $request->cash_rate,
            'insurance_rate' => $request->insurance_rate,
            '_who_added' => Auth::User()->id
        ]);
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
     * @return Response
     * @throws Exception
     */
    public function destroy($id)
    {
        $status = ClaimRate::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('claim_rates.claim_rates_deleted_successfully'),
                'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'),
            'status' => false]);
    }
}
