<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\InvoiceResource;
use App\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends ApiController
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['patient', 'items.medical_service'])
            ->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn ($pq) => $pq->where('surname', 'like', "%{$search}%")
                      ->orWhere('othername', 'like', "%{$search}%"));
            });
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $paginator = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, InvoiceResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $detail = $this->invoiceService->getInvoiceDetail($id);

        $invoice = Invoice::with(['patient', 'items.medical_service', 'payments'])->findOrFail($id);

        return $this->success(new InvoiceResource($invoice));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_id'                 => 'required|exists:appointments,id',
            'items'                          => 'required|array|min:1',
            'items.*.medical_service_id'     => 'required|exists:medical_services,id',
            'items.*.qty'                    => 'required|integer|min:1',
            'items.*.price'                  => 'required|numeric|min:0',
            'items.*.doctor_id'              => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->invoiceService->createInvoice(
            $request->input('appointment_id'),
            $request->input('items')
        );

        if (!$result['status']) {
            return $this->error($result['message'], 500);
        }

        return $this->success(null, $result['message'], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->invoiceService->deleteInvoice($id);

        if (!$result['status']) {
            return $this->error($result['message'], 404);
        }

        return $this->success(null, $result['message']);
    }

    public function search(Request $request): JsonResponse
    {
        $results = $this->invoiceService->searchInvoices($request->input('q', ''));

        return $this->success($results);
    }

    public function amount(int $id): JsonResponse
    {
        $data = $this->invoiceService->getInvoiceAmountData($id);

        return $this->success($data);
    }

    public function procedures(int $id): JsonResponse
    {
        $procedures = $this->invoiceService->getInvoiceProcedures($id);

        return $this->success($procedures);
    }

    public function approveDiscount(Request $request, int $id): JsonResponse
    {
        $result = $this->invoiceService->approveDiscount(
            $id,
            $request->user()->id,
            $request->input('reason')
        );

        if (!$result['status']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, $result['message']);
    }

    public function rejectDiscount(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->invoiceService->rejectDiscount(
            $id,
            $request->user()->id,
            $request->input('reason')
        );

        if (!$result['status']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, $result['message']);
    }

    public function setCredit(Request $request, int $id): JsonResponse
    {
        $result = $this->invoiceService->setCredit($id, $request->user()->id);

        if (!$result['status']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, $result['message']);
    }
}
