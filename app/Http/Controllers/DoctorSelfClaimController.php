<?php

namespace App\Http\Controllers;

use App\DoctorClaim;
use App\Http\Helper\NameHelper;
use App\Services\DoctorModuleClaimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DoctorSelfClaimController extends Controller
{
    private DoctorModuleClaimService $service;

    public function __construct(DoctorModuleClaimService $service)
    {
        $this->service = $service;
        // 医生自助提成：数据已按 _who_added 限定本人，这里挡住不该进本模块的角色。
        // manage-doctor-claims 是后台审批他人提成的权限（DoctorClaimController），别混用。
        //
        // 只挂 can:view-appointments 是挡不住的 —— 那条权限在
        // DefaultRolePermissionsSeeder 里同时发给了医生、护士、前台和管理员。
        // 列表按 _who_added 过滤所以看着是空的，但 POST /claims 不看这个。
        // 「是不是医生」由 users.is_doctor 回答，与 DoctorReportController 同一判据。
        $this->middleware('can:view-appointments');
        $this->middleware('doctor');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getClaimsList();
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {})
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('patient', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('insurance_amount', function ($row) {
                    return $this->service->calculateInsuranceClaim($row->insurance_amount);
                })
                ->addColumn('cash_amount', function ($row) {
                    return $this->service->calculateCashClaim($row->cash_amount);
                })
                ->addColumn('total_claim_amount', function ($row) {
                    return number_format($this->service->calculateTotalClaim($row->insurance_amount, $row->cash_amount));
                })
                ->addColumn('action', function ($row) {
                    $action_btn = '';
                    if ($row->status == DoctorClaim::STATUS_PENDING) {
                        $action_btn = '
                       <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')"> ' . __('common.edit') . '</a>
                            </li>
                             <li>
                                <a  href="#" onclick="deleteRecord(' . $row->id . ')"  >' . __('common.delete') . '</a>
                            </li>
                    ';
                    }
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                             ' . $action_btn . '
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['amount', 'action'])
                ->make(true);
        }
        return view('doctor_self_claims.index');
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'appointment_id' => 'required|integer|exists:appointments,id',
            // 只校验 required 的话，负数和 "abc" 都收 —— 后者落库成 0，
            // 前者直接冲掉当月提成合计。
            'amount' => 'required|numeric|min:0',
        ])->validate();

        try {
            $claim = $this->service->createClaim((int) $request->appointment_id, (float) $request->amount);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false], 422);
        }

        if ($claim === null) {
            return response()->json(['message' => __('doctor_claims.no_claim_rate_in_system'), 'status' => false]);
        }
        return response()->json(['message' => __('doctor_claims.claim_submitted_successfully'), 'status' => true]);
    }

    public function edit($id)
    {
        $claim = $this->service->getClaimForEdit((int) $id);
        return response()->json($claim);
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ])->validate();
        $status = $this->service->updateClaim((int) $id, (float) $request->amount);
        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    public function destroy($id)
    {
        $status = $this->service->deleteClaim((int) $id);
        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }
}
