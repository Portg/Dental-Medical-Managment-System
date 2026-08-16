<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\InvoicePaymentResource;
use App\InvoicePayment;
use App\Services\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Invoice Payments
 */
class InvoicePaymentController extends ApiController
{
    public function __construct(
        protected InvoicePaymentService $service
    ) {
        $this->middleware('can:edit-invoices');
    }

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
        // 付款方式白名单与条件必填走 InvoicePaymentService::detailRules()，
        // 与 Web 入口同一份规则 —— 各写各的就会出现「Web 拦得住、API 绕得过」。
        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'invoice_id'   => 'required|exists:invoices,id',
        ] + InvoicePaymentService::detailRules());

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $payment = $this->service->createPayment($request->only(['amount', 'payment_method', 'payment_date', 'invoice_id', 'account_name', 'cheque_no', 'bank_name', 'insurance_company_id', 'self_account_id']));
        } catch (\RuntimeException $e) {
            // 超收 / 折扣待审批
            return $this->error($e->getMessage(), 422);
        }

        if (!$payment) {
            return $this->error('Failed to create payment', 500);
        }

        $payment->load('addedBy');

        return $this->success(new InvoicePaymentResource($payment), 'Payment created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ] + InvoicePaymentService::detailRules());

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $status = $this->service->updatePayment($id, $request->only(['amount', 'payment_method', 'payment_date', 'account_name', 'cheque_no', 'bank_name', 'insurance_company_id', 'self_account_id']));
        } catch (\RuntimeException $e) {
            // 超收 / 折扣待审批 / 储值收款不可就地改
            return $this->error($e->getMessage(), 422);
        }

        if (!$status) {
            return $this->error('Failed to update payment', 500);
        }

        $payment = InvoicePayment::with('addedBy')->find($id);

        return $this->success(new InvoicePaymentResource($payment), 'Payment updated');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $status = $this->service->deletePayment($id);
        } catch (\RuntimeException $e) {
            // 账单挂着退费时不许撤销收款
            return $this->error($e->getMessage(), 422);
        }

        if (!$status) {
            return $this->error('Failed to delete payment', 500);
        }

        return $this->success(null, 'Payment deleted');
    }

    public function processMixed(Request $request, int $invoiceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payments'          => 'required|array|min:1',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payment_date'      => 'nullable|date',
        ] + InvoicePaymentService::detailRules('payments.*.'));

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
