<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\InsuranceCompany;
use App\Invoice;
use App\InvoicePayment;
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
}
