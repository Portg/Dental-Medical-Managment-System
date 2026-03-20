<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\Patient;
use App\Refund;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

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

        if ($refund->approval_status !== Refund::APPROVAL_APPROVED) {
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
        // AG-005: bcmath for monetary precision
        $maxRefundable = bcsub((string)$invoice->paid_amount, (string)($invoice->total_refunded ?? 0), 2);

        return [
            'invoice_no' => $invoice->invoice_no,
            'paid_amount' => $invoice->paid_amount,
            'refunded_amount' => $invoice->total_refunded ?? 0,
            'max_refundable' => bccomp($maxRefundable, '0', 2) >= 0 ? $maxRefundable : '0',
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
        DB::beginTransaction();
        try {
            // 锁住 invoice 行，防止并发超额退款（TOCTOU）
            $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

            // BR-040: 锁内检查重复退款，防止并发绕过
            $existingRefund = Refund::where('invoice_id', $data['invoice_id'])
                ->whereIn('approval_status', [Refund::APPROVAL_PENDING, Refund::APPROVAL_APPROVED])
                ->lockForUpdate()
                ->first();
            if ($existingRefund) {
                DB::rollBack();
                return ['message' => __('invoices.refund_already_exists'), 'status' => false];
            }

            // 锁内检查退款金额，确保读到最新的 paid_amount（AG-005: bcmath）
            $maxRefundable = bcsub((string)$invoice->paid_amount, (string)($invoice->total_refunded ?? 0), 2);
            if (bccomp((string)$data['refund_amount'], $maxRefundable, 2) > 0) {
                DB::rollBack();
                return [
                    'message' => __('invoices.refund_exceeds_paid', ['max' => number_format($maxRefundable, 2)]),
                    'status' => false,
                ];
            }

            // BR-037, BR-038: Determine approval status
            $approvalStatus = Refund::APPROVAL_PENDING;
            $approvedBy = null;
            $approvedAt = null;

            if (bccomp((string)$data['refund_amount'], (string)self::REFUND_APPROVAL_THRESHOLD, 2) <= 0) {
                $approvalStatus = Refund::APPROVAL_APPROVED;
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

            if ($approvalStatus === Refund::APPROVAL_APPROVED) {
                $this->executeRefund($refund, $invoice);
            }

            DB::commit();

            $message = $approvalStatus === Refund::APPROVAL_APPROVED
                ? __('invoices.refund_processed_successfully')
                : __('invoices.refund_pending_approval');

            return [
                'message' => $message,
                'status' => true,
                'refund_id' => $refund->id,
                'needs_approval' => $approvalStatus === Refund::APPROVAL_PENDING,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('processRefund failed', ['error' => $e->getMessage()]);
            return ['message' => __('messages.error_occurred'), 'status' => false];
        }
    }

    /**
     * Approve a pending refund.
     *
     * @return array{message: string, status: bool}
     */
    public function approveRefund(int $id, int $approverId): array
    {
        DB::beginTransaction();
        try {
            // 锁住 refund 行，防止并发重复审批
            $refund = Refund::lockForUpdate()->findOrFail($id);

            if ($refund->approval_status !== Refund::APPROVAL_PENDING) {
                DB::rollBack();
                return ['message' => __('invoices.refund_not_pending'), 'status' => false];
            }

            $refund->approval_status = Refund::APPROVAL_APPROVED;
            $refund->approved_by = $approverId;
            $refund->approved_at = now();
            $refund->save();

            // 锁住 invoice 行，防止并发修改 paid_amount
            $invoice = Invoice::lockForUpdate()->findOrFail($refund->invoice_id);
            $this->executeRefund($refund, $invoice);

            DB::commit();
            return ['message' => __('invoices.refund_approved_successfully'), 'status' => true];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('approveRefund failed', ['error' => $e->getMessage()]);
            return ['message' => __('messages.error_occurred'), 'status' => false];
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

        if ($refund->approval_status !== Refund::APPROVAL_PENDING) {
            return ['message' => __('invoices.refund_not_pending'), 'status' => false];
        }

        $refund->approval_status = Refund::APPROVAL_REJECTED;
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

        if ($refund->approval_status === Refund::APPROVAL_APPROVED) {
            return ['message' => __('invoices.cannot_delete_approved_refund'), 'status' => false];
        }

        $refund->delete();

        return ['message' => __('invoices.refund_deleted_successfully'), 'status' => true];
    }

    // ─── Private helpers ─────────────────────────────────────────

    // ─── DataTable builders ─────────────────────────────────────

    /**
     * Build DataTables response for the refund index page.
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('refund_no', function ($row) {
                return '<a href="' . url('refunds/' . $row->id) . '">' . e($row->refund_no) . '</a>';
            })
            ->addColumn('patient_name', function ($row) {
                return $row->patient ? $row->patient->full_name : '-';
            })
            ->addColumn('invoice_no', function ($row) {
                return $row->invoice ? '<a href="' . url('invoices/' . $row->invoice_id) . '">' . e($row->invoice->invoice_no) . '</a>' : '-';
            })
            ->addColumn('refund_amount', function ($row) {
                return number_format($row->refund_amount, 2);
            })
            ->addColumn('refund_date', function ($row) {
                return $row->refund_date ? $row->refund_date->format('Y-m-d') : '-';
            })
            ->addColumn('status', function ($row) {
                $statusClasses = [
                    Refund::APPROVAL_PENDING  => 'label-warning',
                    Refund::APPROVAL_APPROVED => 'label-success',
                    Refund::APPROVAL_REJECTED => 'label-danger',
                ];
                $class = $statusClasses[$row->approval_status] ?? 'label-default';
                $label = \App\DictItem::nameByCode('refund_approval_status', $row->approval_status) ?? $row->approval_status;
                return '<span class="label ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown">';
                $btn .= __('common.action') . ' <i class="fa fa-angle-down"></i></button>';
                $btn .= '<ul class="dropdown-menu" role="menu">';
                $btn .= '<li><a href="' . url('refunds/' . $row->id) . '">' . __('common.view') . '</a></li>';

                if ($row->approval_status === Refund::APPROVAL_PENDING) {
                    $btn .= '<li><a href="#" onclick="approveRefund(' . $row->id . ')">' . __('invoices.approve_refund') . '</a></li>';
                    $btn .= '<li><a href="#" onclick="rejectRefund(' . $row->id . ')">' . __('invoices.reject_refund') . '</a></li>';
                }

                if ($row->approval_status === Refund::APPROVAL_APPROVED) {
                    $btn .= '<li><a href="' . url('refunds/' . $row->id . '/print') . '" target="_blank">' . __('print.print_refund') . '</a></li>';
                }

                $btn .= '<li class="divider"></li>';
                $btn .= '<li><a href="#" onclick="deleteRefund(' . $row->id . ')" class="text-danger">' . __('common.delete') . '</a></li>';
                $btn .= '</ul></div>';
                return $btn;
            })
            ->rawColumns(['refund_no', 'invoice_no', 'status', 'action'])
            ->make(true);
    }

    /**
     * Build DataTables response for pending refund approvals.
     */
    public function buildPendingApprovalsDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('refund_no', function ($row) {
                return $row->refund_no;
            })
            ->addColumn('patient_name', function ($row) {
                return $row->patient ? $row->patient->full_name : '-';
            })
            ->addColumn('refund_amount', function ($row) {
                return number_format($row->refund_amount, 2);
            })
            ->addColumn('refund_reason', function ($row) {
                return $row->refund_reason;
            })
            ->addColumn('requested_by', function ($row) {
                return $row->whoAdded ? $row->whoAdded->othername : '-';
            })
            ->addColumn('requested_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i');
            })
            ->addColumn('action', function ($row) {
                return '
                        <button class="btn btn-sm btn-success" onclick="approveRefund(' . $row->id . ')">
                            <i class="fa fa-check"></i> ' . __('invoices.approve') . '
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectRefund(' . $row->id . ')">
                            <i class="fa fa-times"></i> ' . __('invoices.reject') . '
                        </button>
                    ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // ─── Private helpers ─────────────────────────────────────────

    /**
     * Execute refund logic: update invoice and handle stored_value refund.
     * PRD: BR-041
     */
    private function executeRefund(Refund $refund, Invoice $invoice): void
    {
        // AG-005: bcmath for monetary precision
        $newPaidAmount = bcsub((string)$invoice->paid_amount, (string)$refund->refund_amount, 2);
        $invoice->paid_amount = bccomp($newPaidAmount, '0', 2) >= 0 ? $newPaidAmount : '0';
        $invoice->save();

        // BR-041: stored_value refund goes back to patient balance
        if ($refund->refund_method === 'stored_value') {
            $patient = Patient::lockForUpdate()->find($refund->patient_id);
            if ($patient) {
                $originalBalance = (string)($patient->member_balance ?? '0');
                $patient->member_balance = bcadd($originalBalance, (string)$refund->refund_amount, 2);
                $patient->save();

                if (class_exists('\App\MemberTransaction')) {
                    \App\MemberTransaction::create([
                        'transaction_no' => \App\MemberTransaction::generateTransactionNo(),
                        'transaction_type' => 'Refund',
                        'patient_id' => $patient->id,
                        'amount' => $refund->refund_amount,
                        'balance_before' => $originalBalance,
                        'balance_after' => $patient->member_balance,
                        'description' => __('invoices.refund_to_stored_value', ['refund_no' => $refund->refund_no]),
                        '_who_added' => Auth::id(),
                    ]);
                }
            }
        }
    }
}
