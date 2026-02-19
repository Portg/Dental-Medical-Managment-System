<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\InvoicePayment;
use App\Jobs\SendAppointmentSms;
use App\Jobs\ShareEmailInvoice;
use App\MedicalService;
use App\Patient;
use App\Refund;
use App\Role;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RegressionTest extends TestCase
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

        Bus::fake();

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
            'email'      => 'zhangsan@example.com',
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

        $service = MedicalService::create([
            'name'       => '洗牙',
            'price'      => 200,
            '_who_added' => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'appointment_id'     => $this->appointment->id,
            'patient_id'         => $this->patient->id,
            'subtotal'           => 200,
            'total_amount'       => 200,
            'paid_amount'        => 200,
            'outstanding_amount' => 0,
            'payment_status'     => 'paid',
            '_who_added'         => $this->admin->id,
        ]);

        InvoiceItem::create([
            'qty'                => 1,
            'price'              => 200,
            'invoice_id'         => $this->invoice->id,
            'medical_service_id' => $service->id,
            'doctor_id'          => $doctor->id,
            '_who_added'         => $this->admin->id,
        ]);

        InvoicePayment::create([
            'amount'         => 200,
            'payment_method' => 'Cash',
            'payment_date'   => now()->format('Y-m-d'),
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $branch->id,
            '_who_added'     => $this->admin->id,
        ]);

        $this->invoiceService = new InvoiceService();
    }

    // ─── DataTables JSON structure ───────────────────────────────

    public function test_invoice_datatables_json_structure(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/invoices?draw=1&start=0&length=10', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    // ─── PDF response ────────────────────────────────────────────

    public function test_invoice_pdf_response(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/print-receipt/' . $this->invoice->id);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ─── Refund PDF requires approved ────────────────────────────

    public function test_refund_pdf_requires_approved(): void
    {
        Auth::login($this->admin);

        $refund = Refund::create([
            'refund_no'       => Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 50,
            'refund_reason'   => '测试',
            'refund_date'     => now(),
            'refund_method'   => 'Cash',
            'approval_status' => 'pending',
            'branch_id'       => $this->admin->branch_id,
            '_who_added'      => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/refunds/' . $refund->id . '/print');

        $response->assertStatus(403);
    }

    // ─── Excel export ────────────────────────────────────────────

    public function test_invoice_excel_export_returns_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/export-invoices-report');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(
            str_contains($contentType, 'spreadsheet') || str_contains($contentType, 'excel'),
            "Expected spreadsheet content type, got: {$contentType}"
        );
    }

    // ─── Job dispatch: Invoice email ─────────────────────────────

    public function test_send_invoice_email_dispatches_job(): void
    {
        Auth::login($this->admin);

        $this->invoiceService->sendInvoiceEmail(
            $this->invoice->id,
            'test@example.com',
            '请查收您的账单'
        );

        Bus::assertDispatched(ShareEmailInvoice::class);
    }

    // ─── Job dispatch: Appointment SMS ───────────────────────────

    public function test_appointment_sms_dispatches_job(): void
    {
        dispatch(new SendAppointmentSms('13800138000', '您的预约已确认', 'appointment'));

        Bus::assertDispatched(SendAppointmentSms::class, function ($job) {
            return true;
        });
    }
}
