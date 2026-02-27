<?php

namespace App\Services;

use App\Invoice;
use App\InvoicePayment;
use App\MemberTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\MemberSetting;
use App\Services\MemberService;

class InvoicePaymentService
{
    /**
     * Supported payment methods (PRD 4.1.3).
     */
    public const PAYMENT_METHODS = [
        'Cash' => ['label' => 'invoices.cash', 'fee' => 0],
        'WeChat' => ['label' => 'invoices.wechat_pay', 'fee' => 0.006],
        'Alipay' => ['label' => 'invoices.alipay', 'fee' => 0.006],
        'BankCard' => ['label' => 'invoices.bank_card', 'fee' => 0.005],
        'StoredValue' => ['label' => 'invoices.stored_value', 'fee' => 0],
        'Insurance' => ['label' => 'invoices.insurance', 'fee' => 0],
        'Online Wallet' => ['label' => 'invoices.online_wallet', 'fee' => 0],
        'Mobile Money' => ['label' => 'invoices.mobile_money', 'fee' => 0],
        'Cheque' => ['label' => 'invoices.cheque', 'fee' => 0],
        'Self Account' => ['label' => 'invoices.self_account', 'fee' => 0],
        'Credit' => ['label' => 'invoices.credit', 'fee' => 0],
    ];

    /**
     * Get payments for an invoice.
     */
    public function getPaymentsByInvoice(int $invoiceId): Collection
    {
        return InvoicePayment::where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get payment with insurance company info.
     */
    public function getPaymentForEdit(int $id): ?object
    {
        return DB::table('invoice_payments')
            ->leftJoin('insurance_companies', 'insurance_companies.id',
                'invoice_payments.insurance_company_id')
            ->where('invoice_payments.id', $id)
            ->select('invoice_payments.*', 'insurance_companies.name')
            ->first();
    }

    /**
     * Create a new payment record.
     */
    public function createPayment(array $data): ?InvoicePayment
    {
        return InvoicePayment::create([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'cheque_no' => $data['cheque_no'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'invoice_id' => $data['invoice_id'],
            'insurance_company_id' => $data['insurance_company_id'] ?? null,
            'self_account_id' => $data['self_account_id'] ?? null,
            'branch_id' => Auth::User()->branch_id,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing payment record.
     */
    public function updatePayment(int $id, array $data): bool
    {
        return (bool) InvoicePayment::where('id', $id)->update([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'cheque_no' => $data['cheque_no'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'insurance_company_id' => $data['insurance_company_id'] ?? null,
            'self_account_id' => $data['self_account_id'] ?? null,
            'branch_id' => Auth::User()->branch_id,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a payment record.
     */
    public function deletePayment(int $id): bool
    {
        return (bool) InvoicePayment::where('id', $id)->delete();
    }

    /**
     * Process mixed payment (multiple methods for one invoice).
     *
     * @return array{status: bool, message: string, paid_amount?: float, change_due?: float, new_balance?: float}
     */
    public function processMixedPayment(int $invoiceId, array $payments, ?string $paymentDate = null): array
    {
        $invoice = Invoice::findOrFail($invoiceId);

        // Check if invoice can accept payment
        if (!$invoice->canAcceptPayment()) {
            return ['status' => false, 'message' => __('invoices.discount_approval_required')];
        }

        $totalPayment = collect($payments)->sum('amount');
        $outstanding = $invoice->outstanding_amount;

        if ($totalPayment > $outstanding) {
            return ['status' => false, 'message' => __('invoices.payment_exceeds_outstanding')];
        }

        DB::beginTransaction();
        try {
            $paymentDate = $paymentDate ?? now()->format('Y-m-d');
            $patient = $invoice->patient;

            foreach ($payments as $paymentData) {
                $method = $paymentData['payment_method'];
                $amount = $paymentData['amount'];

                if ($amount <= 0) continue;

                // StoredValue special handling
                if ($method === 'StoredValue') {
                    if (!$patient) {
                        throw new \Exception(__('invoices.patient_required_for_stored_value'));
                    }

                    // Resolve primary member for shared card holders
                    $payingPatient = app(MemberService::class)->resolvePrimaryMember($patient->id);
                    $storedBalance = $payingPatient->member_balance ?? 0;
                    if ($amount > $storedBalance) {
                        throw new \Exception(__('invoices.insufficient_stored_balance'));
                    }

                    $payingPatient->member_balance = $storedBalance - $amount;
                    $payingPatient->save();

                    if (class_exists('\App\MemberTransaction')) {
                        MemberTransaction::create([
                            'transaction_no' => MemberTransaction::generateTransactionNo(),
                            'transaction_type' => 'Consumption',
                            'patient_id' => $payingPatient->id,
                            'amount' => -$amount,
                            'balance_before' => $storedBalance,
                            'balance_after' => $payingPatient->member_balance,
                            'description' => __('invoices.stored_value_payment', ['invoice_no' => $invoice->invoice_no]),
                            '_who_added' => Auth::id(),
                        ]);
                    }
                }

                InvoicePayment::create([
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $method,
                    'cheque_no' => $paymentData['cheque_no'] ?? null,
                    'account_name' => $paymentData['account_name'] ?? null,
                    'bank_name' => $paymentData['bank_name'] ?? null,
                    'invoice_id' => $invoice->id,
                    'insurance_company_id' => $paymentData['insurance_company_id'] ?? null,
                    'self_account_id' => $paymentData['self_account_id'] ?? null,
                    'transaction_ref' => $paymentData['transaction_ref'] ?? null,
                    'branch_id' => Auth::user()->branch_id ?? null,
                    '_who_added' => Auth::id(),
                ]);
            }

            // Update invoice paid amount
            $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $totalPayment;
            $invoice->save();

            // Update total consumption
            if ($patient) {
                $patient->total_consumption = ($patient->total_consumption ?? 0) + $totalPayment;
                $patient->save();
            }

            // Update member points (BR-036) — per-payment-method rates
            if ($patient && $patient->memberLevel && MemberSetting::get('points_enabled', true)) {
                $level = $patient->memberLevel;
                $totalPoints = 0;

                foreach ($payments as $pd) {
                    $m = $pd['payment_method'];
                    $a = (float) $pd['amount'];
                    if ($a <= 0) continue;

                    $rate = $level->getPointsRateForMethod($m);
                    $totalPoints += floor($a * $rate);
                }

                if ($totalPoints > 0) {
                    $patient->member_points = ($patient->member_points ?? 0) + $totalPoints;
                    $patient->save();

                    // Record points transaction with optional expiry
                    $expiryDays = (int) MemberSetting::get('points_expiry_days', 0);
                    MemberTransaction::create([
                        'transaction_no'    => MemberTransaction::generateTransactionNo(),
                        'transaction_type'  => 'Points',
                        'patient_id'        => $patient->id,
                        'amount'            => 0,
                        'balance_before'    => $patient->member_balance,
                        'balance_after'     => $patient->member_balance,
                        'points_change'     => $totalPoints,
                        'points_expires_at' => $expiryDays > 0 ? now()->addDays($expiryDays)->toDateString() : null,
                        'description'       => __('members.type_points') . ' +' . $totalPoints,
                        'invoice_id'        => $invoice->id,
                        '_who_added'        => Auth::id(),
                    ]);
                }
            }

            // Auto-upgrade check
            if ($patient && $patient->member_level_id) {
                $patient->refresh();
                app(MemberService::class)->checkAndUpgrade($patient);
            }

            DB::commit();

            $change = max(0, $totalPayment - $outstanding);

            return [
                'status' => true,
                'message' => __('invoices.payment_recorded_successfully'),
                'paid_amount' => $totalPayment,
                'change_due' => $change,
                'new_balance' => $invoice->outstanding_amount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get translated payment methods list.
     */
    public function getPaymentMethodsList(): array
    {
        $methods = [];
        foreach (self::PAYMENT_METHODS as $key => $value) {
            $methods[] = [
                'value' => $key,
                'label' => __($value['label']),
                'fee' => $value['fee'],
            ];
        }
        return $methods;
    }

    /**
     * Calculate change due for a payment.
     */
    public function calculateChange(int $invoiceId, float $receivedAmount): array
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $outstanding = $invoice->outstanding_amount;
        $change = max(0, $receivedAmount - $outstanding);

        return [
            'outstanding' => $outstanding,
            'received' => $receivedAmount,
            'change_due' => $change,
        ];
    }
}
