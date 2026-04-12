<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
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
    public function update_staff_rejects_nonexistent_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson('/invoices/' . $this->invoice->id, [
                'doctor_id' => 99999,
            ]);

        $response->assertStatus(422);
    }
}
