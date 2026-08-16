<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InvoicePaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private MedicalService $service;
    private Invoice $invoice;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole  = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
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

        $this->appointment = Appointment::create([
            'start_date'        => now()->addDay()->format('Y-m-d'),
            'end_date'          => now()->addDay()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information'  => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->service = MedicalService::create([
            'name'       => '洗牙',
            'price'      => 200,
            '_who_added' => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'         => 'INV20260001',
            'appointment_id'     => $this->appointment->id,
            'patient_id'         => $this->patient->id,
            'total_amount'       => 200,
            'paid_amount'        => 0,
            'outstanding_amount' => 200,
            '_who_added'         => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_invoice_payments(): void
    {
        // Create a payment first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/invoice-payments');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_payment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'     => $this->invoice->id,
            'payment_method' => 'Cash',
            'amount'         => 100,
        ]);
    }

    public function test_create_payment_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    /**
     * 收款规则不能只在 Web 入口成立。
     *
     * 付款方式白名单与「支票要支票号 / 保险要保司 / 往来账户要账户」这几条，
     * 此前只有 Web 单笔入口有；API 侧 payment_method 只校验是字符串，
     * 换个入口就全绕过去了。规则现在集中在
     * InvoicePaymentService::detailRules()，四个入口共用。
     */
    public function test_create_payment_rejects_an_unknown_method(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Bitcoin',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ])
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    public function test_create_payment_requires_the_details_a_method_implies(): void
    {
        // 支票没有支票号与银行
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cheque',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ])
            ->assertStatus(422);

        // 保险没有保险公司
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Insurance',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ])
            ->assertStatus(422);

        // 往来账户没有账户
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Self Account',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ])
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    /**
     * 单笔收款也不能超收。此前只有混合支付这一条路上有欠款判断，
     * /invoice-payments 与 /payments 都能给欠款 200 的账单登记 500。
     */
    public function test_create_payment_cannot_exceed_the_outstanding_amount(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 500,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ])
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
    }

    public function test_mixed_payment_rejects_an_unknown_method(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments/' . $this->invoice->id . '/process-mixed', [
                'payments' => [['payment_method' => 'Bitcoin', 'amount' => 100]],
            ])
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_payment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $paymentId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/invoice-payments/{$paymentId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $paymentId);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_payment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $paymentId = $create->json('data.id');

        // 改成支票就必须给出支票号与银行 —— 这条规则 Web 端一直有，
        // API 侧此前只校验 payment_method 是字符串，能存下一张查不到号的支票。
        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/invoice-payments/{$paymentId}", [
                'amount'         => 150,
                'payment_method' => 'Cheque',
                'payment_date'   => now()->format('Y-m-d'),
                'cheque_no'      => 'CHQ-77',
                'bank_name'      => '建设银行',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('invoice_payments', [
            'id'             => $paymentId,
            'amount'         => 150,
            'payment_method' => 'Cheque',
            'cheque_no'      => 'CHQ-77',
            'bank_name'      => '建设银行',
        ]);
    }

    public function test_update_payment_requires_the_details_a_method_implies(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $paymentId = $create->json('data.id');

        $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/invoice-payments/{$paymentId}", [
                'amount'         => 100,
                'payment_method' => 'Cheque',
                'payment_date'   => now()->format('Y-m-d'),
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('invoice_payments', [
            'id'             => $paymentId,
            'payment_method' => 'Cash',
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_payment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoice-payments', [
                'amount'         => 100,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
                'invoice_id'     => $this->invoice->id,
            ]);

        $paymentId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/invoice-payments/{$paymentId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Payment Methods ───────────────────────────────────────────

    public function test_payment_methods(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/invoice-payments/payment-methods');

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
    }

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/invoice-payments')->assertStatus(401);
        $this->postJson('/api/v1/invoice-payments', [])->assertStatus(401);
    }
}
