<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\QuotationResource;
use App\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuotationController extends ApiController
{
    public function __construct(
        protected QuotationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'quotation_no', 'start_date', 'end_date']);
        $quotations = $this->service->getQuotationList($filters);

        return $this->success($quotations);
    }

    public function show(int $id): JsonResponse
    {
        $quotation = Quotation::with(['items.medical_service'])->find($id);

        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }

        return $this->success(new QuotationResource($quotation));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id'                  => 'required|exists:patients,id',
            'items'                       => 'required|array|min:1',
            'items.*.qty'                 => 'required|integer|min:1',
            'items.*.amount'             => 'required|numeric|min:0',
            'items.*.medical_service_id' => 'required|exists:medical_services,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $quotation = $this->service->createQuotation(
            $request->input('patient_id'),
            $request->input('items'),
            Auth::id()
        );

        if (!$quotation) {
            return $this->error('Failed to create quotation', 500);
        }

        $quotation->load(['items.medical_service']);

        return $this->success(new QuotationResource($quotation), 'Quotation created', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $quotation = Quotation::find($id);

        if (!$quotation) {
            return $this->error('Quotation not found', 404);
        }

        $quotation->delete();

        return $this->success(null, 'Quotation deleted');
    }
}
