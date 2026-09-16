<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\InsuranceCompany;
use App\Invoice;
use App\InvoicePayment;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\SelfAccount;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientBillingTabTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Invoice $invoice;
    private Invoice $overdueInvoice;
    private InvoicePayment $payment;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);

        $role = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $perm = Permission::create(['name' => 'Edit Invoices', 'slug' => 'edit-invoices', 'module' => 'invoices']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        $perm2 = Permission::create(['name' => 'View Invoices', 'slug' => 'view-invoices', 'module' => 'invoices']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm2->id]);
        $perm3 = Permission::create(['name' => 'View Patients', 'slug' => 'view-patients', 'module' => 'patients']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm3->id]);
        // 补收欠款要 collect-payments（收款已从开单/改单里拆出来，
        // 见 2026_08_17_100000 迁移与 InvoiceController::addOverduePayment 的注释）。
        // firstOrCreate：这条权限由迁移建好，create 会撞唯一约束。
        $perm4 = Permission::firstOrCreate(
            ['slug' => 'collect-payments'],
            ['name' => '收款', 'module' => '账单管理']
        );
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm4->id]);

        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);
        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $appointment = Appointment::create([
            'start_date'        => now()->format('Y-m-d'),
            'end_date'          => now()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'appointment_id'     => $appointment->id,
            'patient_id'         => $this->patient->id,
            'subtotal'           => 500,
            'total_amount'       => 500,
            'paid_amount'        => 500,
            'outstanding_amount' => 0,
            'payment_status'     => 'paid',
            '_who_added'         => $this->admin->id,
        ]);

        $appointment2 = Appointment::create([
            'start_date'        => now()->format('Y-m-d'),
            'end_date'          => now()->format('Y-m-d'),
            'start_time'        => '11:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->format('Y-m-d') . ' 11:00:00',
        ]);

        $this->overdueInvoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'appointment_id'     => $appointment2->id,
            'patient_id'         => $this->patient->id,
            'subtotal'           => 800,
            'total_amount'       => 800,
            'paid_amount'        => 300,
            'outstanding_amount' => 500,
            'payment_status'     => 'partial',
            '_who_added'         => $this->admin->id,
        ]);

        $this->payment = InvoicePayment::create([
            'amount'         => 500,
            'payment_method' => 'Cash',
            'payment_date'   => now()->format('Y-m-d'),
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $branch->id,
            '_who_added'     => $this->admin->id,
        ]);

        // overdueInvoice 的 paid_amount=300 必须有对应的收款明细。
        // 应用里 paid_amount 只会由收款（processMixedPayment / createPayment）抬高、
        // 由退费压低，产生不出「有已付金额、无收款行」的状态；夹具漏了这一行，
        // 就成了只在测试里存在的账。收款金额现在按明细重算，这种账会当场露馅。
        InvoicePayment::create([
            'amount'         => 300,
            'payment_method' => 'Cash',
            'payment_date'   => now()->format('Y-m-d'),
            'invoice_id'     => $this->overdueInvoice->id,
            'branch_id'      => $branch->id,
            '_who_added'     => $this->admin->id,
        ]);
    }

    /** @test */
    public function patient_detail_displays_total_outstanding_balance(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/patients/' . $this->patient->id);

        $response->assertOk()
                 ->assertViewHas('totalOutstanding', fn($amount) => (float) $amount === 500.0)
                 ->assertSeeText(__('patient.outstanding_balance'))
                 ->assertSee('&yen;500.00', false);
    }

    /** @test */
    public function billing_detail_returns_invoice_with_staff_and_user_list(): void
    {
        $this->invoice->update(['doctor_id' => $this->doctor->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/invoices/' . $this->invoice->id . '/billing-detail');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1)
                 ->assertJsonPath('data.id', $this->invoice->id)
                 ->assertJsonPath('data.doctor_id', $this->doctor->id)
                 ->assertJsonStructure(['data' => [
                     'id', 'invoice_no', 'invoice_date',
                     'total_amount', 'paid_amount', 'outstanding_amount',
                     'payment_status', 'doctor_id', 'nurse_id', 'assistant_id',
                     'users',
                 ]]);
    }

    /** @test */
    public function update_staff_fields_on_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson('/invoices/' . $this->invoice->id, [
                'doctor_id'    => $this->doctor->id,
                'nurse_id'     => null,
                'assistant_id' => null,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('invoices', [
            'id'           => $this->invoice->id,
            'doctor_id'    => $this->doctor->id,
            'nurse_id'     => null,
            'assistant_id' => null,
        ]);
    }

    /** @test */
    public function update_staff_preserves_unspecified_fields(): void
    {
        $nurse = User::factory()->create([
            'role_id'   => $this->admin->role_id,
            'branch_id' => $this->admin->branch_id,
        ]);
        $assistant = User::factory()->create([
            'role_id'   => $this->admin->role_id,
            'branch_id' => $this->admin->branch_id,
        ]);
        $this->invoice->update([
            'doctor_id'    => $this->doctor->id,
            'nurse_id'     => $nurse->id,
            'assistant_id' => $assistant->id,
        ]);

        $newDoctor = User::factory()->create([
            'role_id'   => $this->admin->role_id,
            'branch_id' => $this->admin->branch_id,
        ]);
        $response = $this->actingAs($this->admin)
            ->patchJson('/invoices/' . $this->invoice->id, [
                'doctor_id' => $newDoctor->id,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('invoices', [
            'id'           => $this->invoice->id,
            'doctor_id'    => $newDoctor->id,
            'nurse_id'     => $nurse->id,
            'assistant_id' => $assistant->id,
        ]);
    }

    /** @test */
    public function update_staff_rejects_nonexistent_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson('/invoices/' . $this->invoice->id, [
                'doctor_id' => 99999,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function add_overdue_payment_creates_payment_and_updates_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'         => '200.00',
                'payment_method' => 'Cash',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1)
                 ->assertJsonPath('message', '补收成功')
                 ->assertJsonPath('data.new_outstanding', '300.00');

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'     => $this->overdueInvoice->id,
            'amount'         => '200.00',
            'payment_method' => 'Cash',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id'                 => $this->overdueInvoice->id,
            'paid_amount'        => '500.00',
            'outstanding_amount' => '300.00',
            'payment_status'     => 'partial',
        ]);
    }

    /** @test */
    public function add_overdue_payment_with_discount_reduces_total(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'              => '400.00',
                'additional_discount' => '100.00',
                'payment_method'      => 'Cash',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1)
                 ->assertJsonPath('data.new_outstanding', '0.00');

        $this->assertDatabaseHas('invoices', [
            'id'                 => $this->overdueInvoice->id,
            'outstanding_amount' => '0.00',
            'payment_status'     => 'paid',
        ]);
    }

    /** @test */
    public function add_overdue_payment_with_discount_only_reduces_total(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'additional_discount' => '100.00',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1)
                 ->assertJsonPath('data.new_outstanding', '400.00');

        $this->assertDatabaseHas('invoices', [
            'id'                 => $this->overdueInvoice->id,
            'discount_amount'    => '100.00',
            'total_amount'       => '700.00',
            'outstanding_amount' => '400.00',
            'payment_status'     => 'partial',
        ]);

        $this->assertDatabaseMissing('invoice_payments', [
            'invoice_id' => $this->overdueInvoice->id,
            'amount'     => '0.00',
        ]);
    }

    /** @test */
    public function add_overdue_payment_with_stored_value_deducts_member_balance(): void
    {
        $this->patient->update(['member_balance' => 500]);

        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'         => '200.00',
                'payment_method' => 'StoredValue',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1)
                 ->assertJsonPath('data.new_outstanding', '300.00');

        $this->assertEquals('300.00', (string) $this->patient->fresh()->member_balance);
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'     => $this->overdueInvoice->id,
            'amount'         => '200.00',
            'payment_method' => 'StoredValue',
        ]);
    }

    /** @test */
    public function add_overdue_payment_persists_insurance_metadata(): void
    {
        $insuranceCompany = InsuranceCompany::create([
            'name'       => 'Test Insurance',
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'               => '200.00',
                'payment_method'       => 'Insurance',
                'insurance_company_id' => $insuranceCompany->id,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'            => $this->overdueInvoice->id,
            'amount'                => '200.00',
            'payment_method'        => 'Insurance',
            'insurance_company_id'  => $insuranceCompany->id,
        ]);
    }

    /** @test */
    public function add_overdue_payment_rejects_amount_exceeding_outstanding(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'         => '600.00',
                'payment_method' => 'Cash',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function add_overdue_payment_rejects_on_fully_paid_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->invoice->id . '/add-overdue-payment', [
                'amount'         => '10.00',
                'payment_method' => 'Cash',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function update_payment_method_without_changing_amount(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/payments/' . $this->payment->id, [
                'payment_method' => 'WeChat',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', true);

        $this->assertDatabaseHas('invoice_payments', [
            'id'             => $this->payment->id,
            'payment_method' => 'WeChat',
            'amount'         => '500.00',
        ]);
    }

    /** @test */
    public function update_payment_method_persists_self_account_metadata(): void
    {
        $selfAccount = SelfAccount::create([
            'account_no'     => 'SA-0001',
            'account_holder' => 'Test Holder',
            'is_active'      => true,
            '_who_added'     => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson('/payments/' . $this->payment->id, [
                'payment_method'  => 'Self Account',
                'self_account_id' => $selfAccount->id,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', true);

        $this->assertDatabaseHas('invoice_payments', [
            'id'              => $this->payment->id,
            'payment_method'  => 'Self Account',
            'self_account_id' => $selfAccount->id,
            'amount'          => '500.00',
        ]);
    }

    /** @test */
    public function update_payment_method_rejects_cheque_without_cheque_no(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/payments/' . $this->payment->id, [
                'payment_method' => 'Cheque',
            ]);

        $response->assertStatus(422);
    }

    /**
     * 顶部汇总栏的金额得能单独取到。
     *
     * 那三个数是服务端渲染进 patients/show.blade.php 的，账单页签收款后只重载了
     * 两张 DataTable —— 汇总栏一直停在打开页面时的旧值，前台收完款看不到新的
     * 未付金额，只能强制刷新整页。有了这个接口，JS 才能就地把它改掉。
     */
    /** @test */
    public function billing_summary_endpoint_returns_the_figures_shown_in_the_header(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/patients/' . $this->patient->id . '/billing-summary');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => [
                'total_spending',
                'total_outstanding',
                'member_balance',
                'full_name',
                'patient_no',
                'member_level',
                'open_invoices',
            ]]);

        // 与页面首次渲染同源：两张账单 500（已付清）+ 逾期那张
        $expected = app(\App\Services\PatientService::class)->getBillingSummary($this->patient->id);

        $this->assertEquals($expected['total_spending'], $response->json('data.total_spending'));
        $this->assertEquals($expected['total_outstanding'], $response->json('data.total_outstanding'));

        // 未结清的那张应出现在 open_invoices，供划价勾选并入收款
        $openIds = collect($response->json('data.open_invoices'))->pluck('id')->all();
        $this->assertContains($this->overdueInvoice->id, $openIds);
        $this->assertNotContains($this->invoice->id, $openIds);
    }

    /** @test */
    public function billing_summary_follows_a_payment(): void
    {
        $before = $this->actingAs($this->admin)
            ->getJson('/patients/' . $this->patient->id . '/billing-summary')
            ->json('data.total_outstanding');

        $this->assertGreaterThan(0, (float) $before, '前提：这个患者还有未付余额');

        // 走账单页签真实用的那个入口把逾期那张收满
        $this->actingAs($this->admin)
            ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
                'amount'         => $this->overdueInvoice->outstanding_amount,
                'payment_method' => 'Cash',
                'payment_date'   => now()->format('Y-m-d'),
            ])
            ->assertJson(['status' => 1]);

        $after = $this->actingAs($this->admin)
            ->getJson('/patients/' . $this->patient->id . '/billing-summary')
            ->json('data.total_outstanding');

        $this->assertLessThan(
            (float) $before,
            (float) $after,
            '收款之后未付余额必须跟着降，否则汇总栏刷新了也还是旧数'
        );
    }

    /** @test */
    public function 收费页首屏是划价历史折叠且能列出未结清账单(): void
    {
        $html = $this->actingAs($this->admin)
            ->get('/patients/' . $this->patient->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="billingOutstandingSection"', $html);
        $this->assertStringContainsString(__('invoices.include_outstanding'), $html);
        $this->assertStringContainsString(__('invoices.billing_history'), $html);
        $this->assertStringContainsString('id="billingHistoryCollapse"', $html);
        $this->assertStringNotContainsString('id="billing_sub_billing"', $html);
    }

    /**
     * 划价勾选历史欠费：前端先 POST /billing/create（只吃本次实收），
     * 再用支付总额多出来的部分逐张 POST add-overdue-payment。
     */
    /** @test */
    public function 划价当场收再按勾选补收历史欠费(): void
    {
        $this->grantCreateInvoices();
        $service = $this->makeBillingService(50);
        $open = $this->makeOpenInvoice(100);

        $created = $this->actingAs($this->admin)
            ->postJson('/billing/create', [
                'patient_id'   => $this->patient->id,
                'billing_mode' => 'direct',
                'items'        => [$this->billingLine($service, 50)],
                'payments'     => [['payment_method' => 'Cash', 'amount' => 50]],
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->json('invoice_id');

        $this->actingAs($this->admin)
            ->postJson('/invoices/' . $open->id . '/add-overdue-payment', [
                'amount'         => 100,
                'payment_method' => 'Cash',
            ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('invoices', [
            'id'                 => $created,
            'paid_amount'        => '50.00',
            'outstanding_amount' => '0.00',
            'payment_status'     => 'paid',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id'                 => $open->id,
            'paid_amount'        => '100.00',
            'outstanding_amount' => '0.00',
            'payment_status'     => 'paid',
        ]);
    }

    /** @test */
    public function 多张欠费按勾选顺序补收(): void
    {
        $this->grantCreateInvoices();
        $first  = $this->makeOpenInvoice(60);
        $second = $this->makeOpenInvoice(40);

        $this->actingAs($this->admin)
            ->postJson('/invoices/' . $first->id . '/add-overdue-payment', [
                'amount'         => 60,
                'payment_method' => 'Cash',
            ])
            ->assertJsonPath('status', 1);

        $this->actingAs($this->admin)
            ->postJson('/invoices/' . $second->id . '/add-overdue-payment', [
                'amount'         => 40,
                'payment_method' => 'Cash',
            ])
            ->assertJsonPath('status', 1);

        $this->assertEquals(0, (float) $first->fresh()->outstanding_amount);
        $this->assertEquals(0, (float) $second->fresh()->outstanding_amount);
    }

    /** @test */
    public function 付款不足时新单收满欠费只补一部分(): void
    {
        $this->grantCreateInvoices();
        $service = $this->makeBillingService(50);
        $open = $this->makeOpenInvoice(100);

        // 一共只付 80：新单吃 50，欠费只能补 30
        $this->actingAs($this->admin)
            ->postJson('/billing/create', [
                'patient_id'   => $this->patient->id,
                'billing_mode' => 'direct',
                'items'        => [$this->billingLine($service, 50)],
                'payments'     => [['payment_method' => 'Cash', 'amount' => 50]],
            ])
            ->assertJsonPath('status', true);

        $this->actingAs($this->admin)
            ->postJson('/invoices/' . $open->id . '/add-overdue-payment', [
                'amount'         => 30,
                'payment_method' => 'Cash',
            ])
            ->assertJsonPath('status', 1);

        $this->assertEquals(70, (float) $open->fresh()->outstanding_amount);
        $this->assertEquals('partial', $open->fresh()->payment_status);
    }

    /** @test */
    public function 转前台收费不开收款也不动历史欠费(): void
    {
        $this->grantCreateInvoices();
        $service = $this->makeBillingService(50);
        $open = $this->makeOpenInvoice(100);

        $created = $this->actingAs($this->admin)
            ->postJson('/billing/create', [
                'patient_id'   => $this->patient->id,
                'billing_mode' => 'front_desk',
                'items'        => [$this->billingLine($service, 50)],
                'payments'     => [['payment_method' => 'Cash', 'amount' => 150]],
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->json('invoice_id');

        $invoice = Invoice::find($created);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals(100, (float) $open->fresh()->outstanding_amount);
    }

    /** @test */
    public function 抹零从新单折后应收里扣(): void
    {
        $this->grantCreateInvoices();
        $service = $this->makeBillingService(50.80);

        $id = $this->actingAs($this->admin)
            ->postJson('/billing/create', [
                'patient_id'   => $this->patient->id,
                'billing_mode' => 'direct',
                'round_off'    => 0.80,
                'items'        => [$this->billingLine($service, 50.80)],
                'payments'     => [['payment_method' => 'Cash', 'amount' => 50]],
            ])
            ->assertJsonPath('status', true)
            ->json('invoice_id');

        $invoice = Invoice::find($id);
        $this->assertEquals(50.0, (float) $invoice->total_amount);
        $this->assertEquals(0.8, (float) $invoice->order_discount_amount);
        $this->assertEquals(0.0, (float) $invoice->outstanding_amount);
    }

    /** @test */
    public function 工作台收费按钮带患者和就诊id(): void
    {
        $this->grantCreateInvoices();
        $viewAppt = Permission::firstOrCreate(
            ['slug' => 'view-appointments'],
            ['name' => 'View Appointments', 'module' => 'appointments']
        );
        RolePermission::firstOrCreate([
            'role_id'       => $this->admin->role_id,
            'permission_id' => $viewAppt->id,
        ]);

        $appointmentId = $this->overdueInvoice->appointment_id;

        $json = $this->actingAs($this->admin)
            ->getJson('/today-work/data?status=all&date=' . now()->format('Y-m-d'))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($json['data'] ?? [], '今日就诊表应有这条预约');
        $invoiceCell = collect($json['data'])->pluck('act_invoice')->implode(' ');
        $this->assertStringContainsString(
            'quickInvoice(' . $this->patient->id . ',' . $appointmentId . ')',
            $invoiceCell
        );
    }

    private function grantCreateInvoices(): void
    {
        $perm = Permission::firstOrCreate(
            ['slug' => 'create-invoices'],
            ['name' => 'Create Invoices', 'module' => 'invoices']
        );
        RolePermission::firstOrCreate([
            'role_id'       => $this->admin->role_id,
            'permission_id' => $perm->id,
        ]);
    }

    private function makeBillingService(float $price): MedicalService
    {
        return MedicalService::create([
            'name'       => '口腔检查',
            'price'      => $price,
            '_who_added' => $this->admin->id,
        ]);
    }

    private function billingLine(MedicalService $service, float $price): array
    {
        return [
            'medical_service_id' => $service->id,
            'qty'                => 1,
            'price'              => $price,
            'discount_rate'      => 100,
            'discounted_price'   => $price,
            'actual_paid'        => $price,
            'arrears'            => 0,
        ];
    }

    private function makeOpenInvoice(float $amount): Invoice
    {
        return Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'patient_id'         => $this->patient->id,
            'subtotal'           => $amount,
            'total_amount'       => $amount,
            'paid_amount'        => 0,
            'outstanding_amount' => $amount,
            'payment_status'     => 'unpaid',
            '_who_added'         => $this->admin->id,
        ]);
    }
}
