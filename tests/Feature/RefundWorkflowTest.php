<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\Refund;
use App\Role;
use App\Services\RefundService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RefundWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Invoice $invoice;
    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => 'true']);
        $role   = Role::create(['name' => 'Administrator']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        Auth::login($this->admin);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $appointment = Appointment::create([
            'start_date'       => now()->format('Y-m-d'),
            'end_date'         => now()->format('Y-m-d'),
            'start_time'       => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->admin->id,
            'branch_id'        => $branch->id,
            '_who_added'       => $this->admin->id,
            'sort_by'          => now()->format('Y-m-d') . ' 10:00:00',
        ]);

        $service = MedicalService::create([
            'name'       => '种植牙',
            'price'      => 1000,
            '_who_added' => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'appointment_id'     => $appointment->id,
            'patient_id'         => $this->patient->id,
            'subtotal'           => 1000,
            'total_amount'       => 1000,
            'paid_amount'        => 1000,
            'outstanding_amount' => 0,
            'payment_status'     => 'paid',
            '_who_added'         => $this->admin->id,
        ]);

        InvoiceItem::create([
            'qty'                => 1,
            'price'              => 1000,
            'invoice_id'         => $this->invoice->id,
            'medical_service_id' => $service->id,
            'doctor_id'          => $this->admin->id,
            '_who_added'         => $this->admin->id,
        ]);

        $this->refundService = new RefundService();
    }

    // ─── BR-037: Auto-approved below threshold ───────────────────

    public function test_refund_below_threshold_auto_approved(): void
    {
        $result = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 50,
            'refund_reason' => '患者不满意',
            'refund_method' => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertFalse($result['needs_approval'] ?? false);

        $refund = Refund::find($result['refund_id']);
        $this->assertEquals('approved', $refund->approval_status);

        // paid_amount should decrease
        $this->assertEquals(950, $this->invoice->fresh()->paid_amount);
    }

    // ─── BR-038: Pending above threshold ─────────────────────────

    public function test_refund_above_threshold_pending(): void
    {
        $result = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 500,
            'refund_reason' => '治疗方案变更',
            'refund_method' => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertTrue($result['needs_approval']);

        $refund = Refund::find($result['refund_id']);
        $this->assertEquals('pending', $refund->approval_status);

        // paid_amount should NOT change yet
        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount);
    }

    // ─── Exceeds paid amount ─────────────────────────────────────

    public function test_refund_exceeds_paid_rejected(): void
    {
        $result = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 2000,
            'refund_reason' => '全额退款',
            'refund_method' => 'Cash',
        ]);

        $this->assertFalse($result['status']);
    }

    // ─── BR-040: Duplicate refund ────────────────────────────────

    public function test_duplicate_refund_rejected(): void
    {
        // First refund (pending)
        $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 500,
            'refund_reason' => '第一次退款',
            'refund_method' => 'Cash',
        ]);

        // Second refund on same invoice
        $result = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 200,
            'refund_reason' => '重复退款',
            'refund_method' => 'Cash',
        ]);

        $this->assertFalse($result['status']);
    }

    // ─── Approve pending refund ──────────────────────────────────

    public function test_approve_pending_refund(): void
    {
        $createResult = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 500,
            'refund_reason' => '等待审批',
            'refund_method' => 'Cash',
        ]);

        $result = $this->refundService->approveRefund($createResult['refund_id'], $this->admin->id);

        $this->assertTrue($result['status']);

        $refund = Refund::find($createResult['refund_id']);
        $this->assertEquals('approved', $refund->approval_status);

        // paid_amount should decrease after approval
        $this->assertEquals(500, $this->invoice->fresh()->paid_amount);
    }

    // ─── Reject pending refund ───────────────────────────────────

    public function test_reject_pending_refund(): void
    {
        $createResult = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 500,
            'refund_reason' => '等待审批',
            'refund_method' => 'Cash',
        ]);

        $result = $this->refundService->rejectRefund($createResult['refund_id'], $this->admin->id, '不符合条件');

        $this->assertTrue($result['status']);

        $refund = Refund::find($createResult['refund_id']);
        $this->assertEquals('rejected', $refund->approval_status);
        $this->assertEquals('不符合条件', $refund->rejection_reason);

        // paid_amount unchanged
        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount);
    }

    // ─── Cannot delete approved refund ───────────────────────────

    public function test_delete_approved_refund_fails(): void
    {
        $createResult = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 50,
            'refund_reason' => '小额退款',
            'refund_method' => 'Cash',
        ]);

        // This was auto-approved since <= 100
        $result = $this->refundService->deleteRefund($createResult['refund_id']);

        $this->assertFalse($result['status']);
    }

    // ─── Refundable amount calculation ───────────────────────────

    public function test_refundable_amount_calculation(): void
    {
        // Create an approved refund first (auto-approved since <= 100)
        $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 50,
            'refund_reason' => '小额退款',
            'refund_method' => 'Cash',
        ]);

        $data = $this->refundService->getRefundableAmount($this->invoice->id);

        // paid_amount after first refund = 950, total_refunded = 50
        $this->assertEquals(950, $data['paid_amount']);
        $this->assertEquals(50, $data['refunded_amount']);
        $this->assertEquals(900, $data['max_refundable']);
    }

    // ─── BR-041: Stored value refund ─────────────────────────────

    public function test_stored_value_refund_restores_balance(): void
    {
        $this->patient->update(['member_balance' => 500]);

        $result = $this->refundService->createRefund([
            'invoice_id'    => $this->invoice->id,
            'refund_amount' => 80, // auto-approved (<= 100)
            'refund_reason' => '储值退款',
            'refund_method' => 'stored_value',
        ]);

        $this->assertTrue($result['status']);
        // member_balance should increase: 500 + 80 = 580
        $this->assertEquals(580, $this->patient->fresh()->member_balance);
    }
}
