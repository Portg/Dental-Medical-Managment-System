<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\InvoicePayment;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Appointment $appointment;
    private Invoice $invoice;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $doctor = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        $this->appointment = Appointment::create([
            'start_date'       => now()->format('Y-m-d'),
            'end_date'         => now()->format('Y-m-d'),
            'start_time'       => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $doctor->id,
            'branch_id'        => $branch->id,
            '_who_added'       => $this->admin->id,
            'sort_by'          => now()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'     => Invoice::InvoiceNo(),
            'appointment_id' => $this->appointment->id,
            'patient_id'     => $this->patient->id,
            '_who_added'     => $this->admin->id,
        ]);

        $this->invoiceService = new InvoiceService();

        // Extend payment_method enum to support all payment types
        DB::statement("ALTER TABLE invoice_payments MODIFY payment_method VARCHAR(50) NULL");
    }

    // ─── totalInvoiceAmount ──────────────────────────────────────

    public function test_total_invoice_amount_sums_items(): void
    {
        InvoiceItem::create(['qty' => 2, 'price' => 100, 'invoice_id' => $this->invoice->id, 'medical_service_id' => $this->createService('洗牙', 100)->id, 'doctor_id' => $this->admin->id, '_who_added' => $this->admin->id]);
        InvoiceItem::create(['qty' => 1, 'price' => 300, 'invoice_id' => $this->invoice->id, 'medical_service_id' => $this->createService('补牙', 300)->id, 'doctor_id' => $this->admin->id, '_who_added' => $this->admin->id]);
        InvoiceItem::create(['qty' => 3, 'price' => 50,  'invoice_id' => $this->invoice->id, 'medical_service_id' => $this->createService('拍片', 50)->id,  'doctor_id' => $this->admin->id, '_who_added' => $this->admin->id]);

        // 2*100 + 1*300 + 3*50 = 650
        $this->assertEquals(650, $this->invoiceService->totalInvoiceAmount($this->invoice->id));
    }

    public function test_total_invoice_amount_empty(): void
    {
        $this->assertEquals(0, $this->invoiceService->totalInvoiceAmount($this->invoice->id));
    }

    // ─── totalInvoicePaidAmount ──────────────────────────────────

    public function test_total_paid_amount(): void
    {
        InvoicePayment::create(['amount' => 200, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 150, 'payment_method' => 'WeChat', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);

        $this->assertEquals(350, $this->invoiceService->totalInvoicePaidAmount($this->invoice->id));
    }

    // ─── invoiceBalance ──────────────────────────────────────────

    public function test_invoice_balance(): void
    {
        InvoiceItem::create(['qty' => 1, 'price' => 500, 'invoice_id' => $this->invoice->id, 'medical_service_id' => $this->createService('种植', 500)->id, 'doctor_id' => $this->admin->id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 200, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);

        // 500 - 200 = 300
        $this->assertEquals(300, $this->invoiceService->invoiceBalance($this->invoice->id));
    }

    // ─── Payment method filters ──────────────────────────────────

    public function test_cash_amount_paid(): void
    {
        InvoicePayment::create(['amount' => 200, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 150, 'payment_method' => 'WeChat', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 100, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);

        $this->assertEquals(300, $this->invoiceService->cashAmountPaid($this->invoice->id));
    }

    public function test_self_account_amount_paid(): void
    {
        InvoicePayment::create(['amount' => 500, 'payment_method' => 'Self Account', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 100, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);

        $this->assertEquals(500, $this->invoiceService->selfAccountAmountPaid($this->invoice->id));
    }

    public function test_insurance_amount_paid(): void
    {
        InvoicePayment::create(['amount' => 800, 'payment_method' => 'Insurance', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);
        InvoicePayment::create(['amount' => 200, 'payment_method' => 'Cash', 'payment_date' => now()->format('Y-m-d'), 'invoice_id' => $this->invoice->id, 'branch_id' => $this->admin->branch_id, '_who_added' => $this->admin->id]);

        $this->assertEquals(800, $this->invoiceService->insuranceAmountPaid($this->invoice->id));
    }

    // ─── InvoiceNo sequential ────────────────────────────────────

    public function test_invoice_no_sequential(): void
    {
        $prefix = 'SF' . date('Ymd');

        $first = Invoice::InvoiceNo();
        $this->assertEquals($prefix . '0002', $first); // 0001 taken by setUp invoice

        Invoice::create([
            'invoice_no'     => $first,
            'appointment_id' => $this->appointment->id,
            '_who_added'     => $this->admin->id,
        ]);

        $second = Invoice::InvoiceNo();
        $this->assertEquals($prefix . '0003', $second);
    }

    // ─── Helper ──────────────────────────────────────────────────

    private function createService(string $name, float $price): MedicalService
    {
        return MedicalService::create([
            'name'       => $name,
            'price'      => $price,
            '_who_added' => $this->admin->id,
        ]);
    }
}
