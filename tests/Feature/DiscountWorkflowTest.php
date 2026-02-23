<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Invoice $invoice;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $patient = Patient::create([
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
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->admin->id,
            'branch_id'        => $branch->id,
            '_who_added'       => $this->admin->id,
            'sort_by'          => now()->format('Y-m-d') . ' 10:00:00',
        ]);

        $service = MedicalService::create([
            'name'       => '种植牙',
            'price'      => 5000,
            '_who_added' => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'     => Invoice::InvoiceNo(),
            'appointment_id' => $appointment->id,
            'patient_id'     => $patient->id,
            'subtotal'       => 5000,
            'total_amount'   => 5000,
            '_who_added'     => $this->admin->id,
        ]);

        InvoiceItem::create([
            'qty'                => 1,
            'price'              => 5000,
            'invoice_id'         => $this->invoice->id,
            'medical_service_id' => $service->id,
            'doctor_id'          => $this->admin->id,
            '_who_added'         => $this->admin->id,
        ]);

        $this->invoiceService = new InvoiceService();
    }

    // ─── Discount threshold (BR-035) ─────────────────────────────

    public function test_discount_below_threshold_no_approval(): void
    {
        $discounts = [
            'subtotal'               => 5000,
            'member_discount_rate'   => 0,
            'member_discount_amount' => 0,
            'item_discount_amount'   => 0,
            'order_discount_rate'    => 5,
            'order_discount_amount'  => 250, // <=500 threshold
            'coupon_discount_amount' => 0,
            'total_discount'         => 250,
            'total_amount'           => 4750,
        ];

        $this->invoice->applyDiscounts($discounts);

        $this->assertEquals('none', $this->invoice->fresh()->discount_approval_status);
    }

    public function test_discount_above_threshold_needs_approval(): void
    {
        $discounts = [
            'subtotal'               => 5000,
            'member_discount_rate'   => 0,
            'member_discount_amount' => 0,
            'item_discount_amount'   => 0,
            'order_discount_rate'    => 20,
            'order_discount_amount'  => 1000, // >500 threshold
            'coupon_discount_amount' => 0,
            'total_discount'         => 1000,
            'total_amount'           => 4000,
        ];

        $this->invoice->applyDiscounts($discounts);

        $this->assertEquals('pending', $this->invoice->fresh()->discount_approval_status);
    }

    // ─── Approve / Reject ────────────────────────────────────────

    public function test_approve_pending_discount(): void
    {
        $this->invoice->update([
            'discount_amount'          => 600,
            'discount_approval_status' => 'pending',
        ]);

        $result = $this->invoiceService->approveDiscount($this->invoice->id, $this->admin->id, '客户VIP');

        $this->assertTrue($result['status']);
        $this->assertEquals('approved', $this->invoice->fresh()->discount_approval_status);
        $this->assertEquals($this->admin->id, $this->invoice->fresh()->discount_approved_by);
    }

    public function test_reject_discount_resets_amounts(): void
    {
        $this->invoice->update([
            'subtotal'                 => 5000,
            'discount_amount'          => 1000,
            'member_discount_amount'   => 200,
            'item_discount_amount'     => 300,
            'order_discount_amount'    => 500,
            'total_amount'             => 4000,
            'discount_approval_status' => 'pending',
        ]);

        $result = $this->invoiceService->rejectDiscount($this->invoice->id, $this->admin->id, '折扣过高');

        $this->assertTrue($result['status']);

        $fresh = $this->invoice->fresh();
        $this->assertEquals('rejected', $fresh->discount_approval_status);
        $this->assertEquals(0, $fresh->discount_amount);
        $this->assertEquals(0, $fresh->member_discount_amount);
        $this->assertEquals(0, $fresh->item_discount_amount);
        $this->assertEquals(0, $fresh->order_discount_amount);
        $this->assertEquals(5000, $fresh->total_amount);
    }

    public function test_cannot_approve_non_pending(): void
    {
        $this->invoice->update([
            'discount_amount'          => 600,
            'discount_approval_status' => 'approved',
        ]);

        $result = $this->invoiceService->approveDiscount($this->invoice->id, $this->admin->id);

        $this->assertFalse($result['status']);
    }

    // ─── canAcceptPayment ────────────────────────────────────────

    public function test_can_accept_payment_after_approval(): void
    {
        $this->invoice->update([
            'discount_amount'          => 600,
            'discount_approval_status' => 'approved',
        ]);

        $this->assertTrue($this->invoice->fresh()->canAcceptPayment());
    }

    // ─── setCredit ───────────────────────────────────────────────

    public function test_set_credit_fails_on_paid_invoice(): void
    {
        $this->invoice->update([
            'total_amount'   => 5000,
            'paid_amount'    => 5000,
            'payment_status' => 'paid',
        ]);

        $result = $this->invoiceService->setCredit($this->invoice->id, $this->admin->id);

        $this->assertFalse($result['status']);
    }
}
