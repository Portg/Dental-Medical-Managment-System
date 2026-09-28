<?php

namespace App\Http\Controllers;

use App\Services\PrepaidItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 剩余项目 —— 已收费但还没做完的那部分（见 PrepaidItemService 的说明）。
 *
 * 权限分两档：看余量跟着「查看账单」走，前台收钱时要能看见；核销是把服务
 * 兑现掉、直接减少诊所的负债，跟着「编辑账单」走。
 */
class PrepaidItemController extends Controller
{
    private PrepaidItemService $service;

    public function __construct(PrepaidItemService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-invoices')->only(['forPatient', 'summary', 'history']);
        $this->middleware('can:edit-invoices')->only(['consume', 'revoke']);
    }

    /**
     * 某位患者的剩余项目（AJAX）
     */
    public function forPatient($patientId)
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->getRemainingForPatient((int) $patientId),
        ]);
    }

    /**
     * 余量汇总 —— 收费窗口的角标（AJAX）
     */
    public function summary($patientId)
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->getPatientSummary((int) $patientId),
        ]);
    }

    /**
     * 核销一次
     */
    public function consume(Request $request, $invoiceItemId)
    {
        $request->validate([
            'qty'             => 'required|numeric|min:0.01',
            'used_at'         => 'nullable|date',
            'medical_case_id' => 'nullable|integer|exists:medical_cases,id',
            'appointment_id'  => 'nullable|integer|exists:appointments,id',
            'doctor_id'       => 'nullable|integer|exists:users,id',
            'notes'           => 'nullable|string|max:255',
        ]);

        $result = $this->service->consume(
            (int) $invoiceItemId,
            (float) $request->input('qty'),
            $request->only(['used_at', 'medical_case_id', 'appointment_id', 'doctor_id', 'notes']),
            Auth::id()
        );

        if (!$result['success']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => __('prepaid.consume_success'),
            'data'    => $result,
        ]);
    }

    /**
     * 撤销一次核销
     */
    public function revoke($usageId)
    {
        $result = $this->service->revoke((int) $usageId, Auth::id());

        if (!$result['success']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => __('prepaid.revoke_success'),
        ]);
    }

    /**
     * 某笔权属的核销流水（AJAX）
     */
    public function history($invoiceItemId)
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->getUsageHistory((int) $invoiceItemId),
        ]);
    }
}
