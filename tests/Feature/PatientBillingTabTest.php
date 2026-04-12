<?php

namespace Tests\Feature;

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

        $branch = Branch::factory()->create();

        $this->admin = User::factory()->create(['branch_id' => $branch->id]);
        $this->doctor = User::factory()->create(['branch_id' => $branch->id]);

        $role = Role::create(['name' => 'admin-billing-test', 'display_name' => 'Admin']);
        foreach (['edit-invoices', 'view-invoices'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'display_name' => $permName, 'guard_name' => 'web']);
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }
        $this->admin->roles()->attach($role);

        $this->patient = Patient::factory()->create(['branch_id' => $branch->id]);

        $this->invoice = Invoice::factory()->create([
            'patient_id' => $this->patient->id,
            'total_amount' => '500.00',
            'paid_amount' => '500.00',
            'outstanding_amount' => '0.00',
            'payment_status' => 'paid',
            'branch_id' => $branch->id,
        ]);

        $this->overdueInvoice = Invoice::factory()->create([
            'patient_id' => $this->patient->id,
            'total_amount' => '800.00',
            'paid_amount' => '300.00',
            'outstanding_amount' => '500.00',
            'payment_status' => 'partial',
            'branch_id' => $branch->id,
        ]);

        $this->payment = InvoicePayment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => '500.00',
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
        ]);
    }

    // ── Tests will be added in Tasks 3, 5, 6 ──
}
