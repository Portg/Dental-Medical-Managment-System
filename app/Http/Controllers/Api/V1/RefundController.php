<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RefundResource;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Refunds
 */
class RefundController extends ApiController
{
    public function __construct(
        protected RefundService $service
    ) {
        $this->middleware('can:manage-refunds');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'start_date', 'end_date', 'search']);
        $refunds = $this->service->getRefundList($filters);

        return $this->success(RefundResource::collection($refunds));
    }

    public function show(int $id): JsonResponse
    {
        $refund = $this->service->getRefundDetail($id);

        return $this->success(new RefundResource($refund));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invoice_id'    => 'required|exists:invoices,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_reason' => 'required|string|max:1000',
            'refund_method' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->createRefund($request->only(['invoice_id', 'refund_amount', 'refund_reason', 'refund_method']));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success([
            'refund_id'      => $result['refund_id'],
            'needs_approval' => $result['needs_approval'] ?? false,
        ], $result['message'], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteRefund($id);

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message']);
    }

    public function approve(int $id): JsonResponse
    {
        $result = $this->service->approveRefund($id, Auth::id());

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message']);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->rejectRefund($id, Auth::id(), $request->input('rejection_reason'));

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message']);
    }

    public function pendingApprovals(): JsonResponse
    {
        $refunds = $this->service->getPendingApprovals();

        return $this->success(RefundResource::collection($refunds));
    }
}
