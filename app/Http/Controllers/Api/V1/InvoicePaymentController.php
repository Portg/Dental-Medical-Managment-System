<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\InvoicePaymentResource;
use App\InvoicePayment;
use App\Services\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoicePaymentController extends ApiController
{
    public function __construct(
        protected InvoicePaymentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = InvoicePayment::with('addedBy')->whereNull('deleted_at');

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->input('invoice_id'));
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, InvoicePaymentResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $payment = InvoicePayment::with('addedBy')->find($id);

        if (!$payment) {
            return $this->error('Payment not found', 404);
        }

        return $this->success(new InvoicePaymentResource($payment));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'               => 'required|numeric|min:0.01',
            'payment_method'       => 'required|string|max:50',
            'payment_date'         => 'required|date',
            'invoice_id'           => 'required|exists:invoices,id',
            'account_name'         => 'nullable|string|max:255',
            'cheque_no'            => 'nullable|string|max:100',
            'bank_name'            => 'nullable|string|max:255',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'self_account_id'      => 'nullable|exists:self_accounts,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $payment = $this->service->createPayment($request->all());

        if (!$payment) {
            return $this->error('Failed to create payment', 500);
        }

        $payment->load('addedBy');

        return $this->success(new InvoicePaymentResource($payment), 'Payment created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'               => 'required|numeric|min:0.01',
            'payment_method'       => 'required|string|max:50',
            'payment_date'         => 'required|date',
            'account_name'         => 'nullable|string|max:255',
            'cheque_no'            => 'nullable|string|max:100',
            'bank_name'            => 'nullable|string|max:255',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'self_account_id'      => 'nullable|exists:self_accounts,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updatePayment($id, $request->all());

        if (!$status) {
            return $this->error('Failed to update payment', 500);
        }

        $payment = InvoicePayment::with('addedBy')->find($id);

        return $this->success(new InvoicePaymentResource($payment), 'Payment updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deletePayment($id);

        if (!$status) {
            return $this->error('Failed to delete payment', 500);
        }

        return $this->success(null, 'Payment deleted');
    }

    public function processMixed(Request $request, int $invoiceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payments'                    => 'required|array|min:1',
            'payments.*.payment_method'   => 'required|string|max:50',
            'payments.*.amount'           => 'required|numeric|min:0.01',
            'payments.*.cheque_no'        => 'nullable|string|max:100',
            'payments.*.account_name'     => 'nullable|string|max:255',
            'payments.*.bank_name'        => 'nullable|string|max:255',
            'payments.*.insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'payments.*.self_account_id'  => 'nullable|exists:self_accounts,id',
            'payment_date'                => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $result = $this->service->processMixedPayment(
            $invoiceId,
            $request->input('payments'),
            $request->input('payment_date')
        );

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success([
            'paid_amount' => $result['paid_amount'] ?? null,
            'change_due'  => $result['change_due'] ?? null,
            'new_balance' => $result['new_balance'] ?? null,
        ], $result['message']);
    }

    public function paymentMethods(): JsonResponse
    {
        $methods = $this->service->getPaymentMethodsList();

        return $this->success($methods);
    }
}
