<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\Patient;
use App\Refund;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RefundService
{
    // 退费审批阈值 (BR-037, BR-038)
    const REFUND_APPROVAL_THRESHOLD = 100;

    // ─── List / filter ────────────────────────────────────────────

    /**
     * Get filtered refund list for DataTables.
     */
    public function getRefundList(array $filters): Collection
    {
        $query = Refund::with(['invoice', 'patient', 'approvedBy', 'whoAdded'])
            ->whereNull('deleted_at');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('refund_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['status'])) {
            $query->where('approval_status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('refund_no', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        NameHelper::addNameSearch($pq, $search);
                    })
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get pending approval refunds.
     */
    public function getPendingApprovals(): Collection
    {
        return Refund::with(['invoice', 'patient', 'whoAdded'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // ─── Single refund ───────────────────────────────────────────

    /**
     * Get refund detail with relations.
     */
    public function getRefundDetail(int $id): Refund
    {
        return Refund::with(['invoice.items.medicalService', 'invoice.payments', 'patient', 'approvedBy', 'whoAdded'])
            ->findOrFail($id);
    }

    /**
     * Get refund for printing (must be approved).
     */
    public function getRefundForPrint(int $id): Refund
    {
        $refund = Refund::with(['invoice', 'patient', 'approvedBy', 'whoAdded', 'branch'])
            ->findOrFail($id);

        if ($refund->approval_status !== 'approved') {
            abort(403, __('invoices.refund_not_approved_for_print'));
        }

        return $refund;
    }

    /**
     * Get refundable amount for an invoice.
     */
    public function getRefundableAmount(int $invoiceId): array
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $maxRefundable = $invoice->paid_amount - ($invoice->total_refunded ?? 0);

        return [
            'invoice_no' => $invoice->invoice_no,
            'paid_amount' => $invoice->paid_amount,
            'refunded_amount' => $invoice->total_refunded ?? 0,
            'max_refundable' => max(0, $maxRefundable),
        ];
    }

    // ─── CUD operations ──────────────────────────────────────────

    /**
     * Create a refund request.
     * PRD: BR-037, BR-038, BR-039, BR-040, BR-041
     *
     * @return array{message: string, status: bool, refund_id?: int, needs_approval?: bool}
     */
    public function createRefund(array $data): array
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        // BR-040: Check existing refund
        $existingRefund = Refund::where('invoice_id', $data['invoice_id'])
            ->whereIn('approval_status', ['pending', 'approved'])
            ->first();
        if ($existingRefund) {
            return ['message' => __('invoices.refund_already_exists'), 'status' => false];
        }

        // Check refund amount
        $maxRefundable = $invoice->paid_amount - ($invoice->total_refunded ?? 0);
        if ($data['refund_amount'] > $maxRefundable) {
            return [
                'message' => __('invoices.refund_exceeds_paid', ['max' => number_format($maxRefundable, 2)]),
                'status' => false,
            ];
        }

        DB::beginTransaction();
        try {
            // BR-037, BR-038: Determine approval status
            $approvalStatus = 'pending';
            $approvedBy = null;
            $approvedAt = null;

            if ($data['refund_amount'] <= self::REFUND_APPROVAL_THRESHOLD) {
                $approvalStatus = 'approved';
                $approvedBy = Auth::id();
                $approvedAt = now();
            }

            $refund = Refund::create([
                'refund_no' => Refund::generateRefundNo(),
                'invoice_id' => $data['invoice_id'],
                'patient_id' => $invoice->patient_id ?? ($invoice->appointment ? $invoice->appointment->patient_id : null),
                'refund_amount' => $data['refund_amount'],
                'refund_reason' => $data['refund_reason'],
                'refund_date' => now(),
                'refund_method' => $data['refund_method'],
                'approval_status' => $approvalStatus,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'branch_id' => Auth::user()->branch_id ?? null,
                '_who_added' => Auth::id(),
            ]);

            if ($approvalStatus === 'approved') {
                $this->executeRefund($refund, $invoice);
            }

            DB::commit();

            $message = $approvalStatus === 'approved'
                ? __('invoices.refund_processed_successfully')
                : __('invoices.refund_pending_approval');

            return [
                'message' => $message,
                'status' => true,
                'refund_id' => $refund->id,
                'needs_approval' => $approvalStatus === 'pending',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['message' => __('messages.error_occurred') . ': ' . $e->getMessage(), 'status' => false];
        }
    }

    /**
     * Approve a pending refund.
     *
     * @return array{message: string, status: bool}
     */
    public function approveRefund(int $id, int $approverId): array
    {
        $refund = Refund::findOrFail($id);

        if ($refund->approval_status !== 'pending') {
            return ['message' => __('invoices.refund_not_pending'), 'status' => false];
        }

        DB::beginTransaction();
        try {
            $refund->approval_status = 'approved';
            $refund->approved_by = $approverId;
            $refund->approved_at = now();
            $refund->save();

            $invoice = Invoice::findOrFail($refund->invoice_id);
            $this->executeRefund($refund, $invoice);

            DB::commit();
            return ['message' => __('invoices.refund_approved_successfully'), 'status' => true];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['message' => __('messages.error_occurred') . ': ' . $e->getMessage(), 'status' => false];
        }
    }

    /**
     * Reject a pending refund.
     *
     * @return array{message: string, status: bool}
     */
    public function rejectRefund(int $id, int $approverId, string $reason): array
    {
        $refund = Refund::findOrFail($id);

        if ($refund->approval_status !== 'pending') {
            return ['message' => __('invoices.refund_not_pending'), 'status' => false];
        }

        $refund->approval_status = 'rejected';
        $refund->approved_by = $approverId;
        $refund->approved_at = now();
        $refund->rejection_reason = $reason;
        $refund->save();

        return ['message' => __('invoices.refund_rejected_successfully'), 'status' => true];
    }

    /**
     * Delete a refund (only pending allowed).
     *
     * @return array{message: string, status: bool}
     */
    public function deleteRefund(int $id): array
    {
        $refund = Refund::findOrFail($id);

        if ($refund->approval_status === 'approved') {
            return ['message' => __('invoices.cannot_delete_approved_refund'), 'status' => false];
        }

        $refund->delete();

        return ['message' => __('invoices.refund_deleted_successfully'), 'status' => true];
    }

    // ─── Private helpers ─────────────────────────────────────────

    /**
     * Execute refund logic: update invoice and handle stored_value refund.
     * PRD: BR-041
     */
    private function executeRefund(Refund $refund, Invoice $invoice): void
    {
        $invoice->paid_amount = max(0, $invoice->paid_amount - $refund->refund_amount);
        $invoice->save();

        // BR-041: stored_value refund goes back to patient balance
        if ($refund->refund_method === 'stored_value') {
            $patient = Patient::find($refund->patient_id);
            if ($patient) {
                $patient->member_balance = ($patient->member_balance ?? 0) + $refund->refund_amount;
                $patient->save();

                if (class_exists('\App\MemberTransaction')) {
                    \App\MemberTransaction::create([
                        'transaction_no' => \App\MemberTransaction::generateTransactionNo(),
                        'transaction_type' => 'Refund',
                        'patient_id' => $patient->id,
                        'amount' => $refund->refund_amount,
                        'balance_before' => $patient->member_balance - $refund->refund_amount,
                        'balance_after' => $patient->member_balance,
                        'description' => __('invoices.refund_to_stored_value', ['refund_no' => $refund->refund_no]),
                        '_who_added' => Auth::id(),
                    ]);
                }
            }
        }
    }
}
