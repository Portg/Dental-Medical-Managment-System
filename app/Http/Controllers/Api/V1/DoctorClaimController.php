<?php

namespace App\Http\Controllers\Api\V1;

use App\DoctorClaim;
use App\Http\Resources\DoctorClaimResource;
use App\Services\DoctorClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DoctorClaimController extends ApiController
{
    public function __construct(
        protected DoctorClaimService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $claims = $this->service->getClaimsList();

        // Enrich each claim with calculated fields
        $enriched = $claims->map(function ($row) {
            $totalClaims = $this->service->getTotalClaims($row);
            $row->total_claims = $totalClaims;
            $row->payment_balance = $this->service->getPaymentBalance($row->id, $totalClaims);
            return $row;
        });

        return $this->success($enriched);
    }

    public function show(int $id): JsonResponse
    {
        $claim = $this->service->getClaim($id);

        if (!$claim) {
            return $this->error('Doctor claim not found', 404);
        }

        // Add calculated fields
        $row = (object) $claim->toArray();
        $row->_who_added = $claim->_who_added;
        $totalClaims = $this->service->getTotalClaims($row);
        $claim->total_claims = $totalClaims;
        $claim->payment_balance = $this->service->getPaymentBalance($id, $totalClaims);

        return $this->success(new DoctorClaimResource($claim));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'claim_amount'     => 'required|numeric|min:0',
            'insurance_amount' => 'required|numeric|min:0',
            'cash_amount'      => 'required|numeric|min:0',
            'claim_rate_id'    => 'required|exists:claim_rates,id',
            'appointment_id'   => 'required|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $claim = DoctorClaim::create(array_merge($request->only([
            'claim_amount', 'insurance_amount', 'cash_amount',
            'claim_rate_id', 'appointment_id',
        ]), ['_who_added' => Auth::id()]));

        return $this->success(new DoctorClaimResource($claim), 'Doctor claim created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'insurance_amount' => 'required|numeric|min:0',
            'cash_amount'      => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updateClaim($id, $request->input('insurance_amount'), $request->input('cash_amount'));

        if (!$status) {
            return $this->error('Failed to update doctor claim', 500);
        }

        $claim = $this->service->getClaim($id);

        return $this->success(new DoctorClaimResource($claim), 'Doctor claim updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteClaim($id);

        if (!$status) {
            return $this->error('Failed to delete doctor claim', 500);
        }

        return $this->success(null, 'Doctor claim deleted');
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'insurance_amount' => 'required|numeric|min:0',
            'cash_amount'      => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->approveClaim($id, $request->input('insurance_amount'), $request->input('cash_amount'));

        if (!$status) {
            return $this->error('Failed to approve doctor claim', 500);
        }

        return $this->success(null, 'Doctor claim approved');
    }
}
