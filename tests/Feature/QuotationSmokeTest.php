<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Quotation;
use App\QuotationItem;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'phone_no' => '123',
        ]);

        $role = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);
        $perm = Permission::create([
            'name' => 'Manage Quotations',
            'slug' => 'manage-quotations',
        ]);
        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);

        $this->admin = User::create([
            'surname' => 'Admin',
            'othername' => 'User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'No',
        ]);

        $this->patient = Patient::create([
            'surname' => 'Test',
            'othername' => 'Patient',
            'gender' => 'Male',
            'has_insurance' => 'No',
            '_who_added' => $this->admin->id,
        ]);
    }

    /**
     * Test that quotation list DataTable AJAX returns valid JSON.
     * Regression test for qi.price SQL error (quotation_items has amount, not price).
     */
    public function test_quotation_list_ajax_returns_valid_json(): void
    {
        // Create a quotation with items to exercise the subquery
        $quotation = Quotation::create([
            'quotation_no' => 'Q-001',
            'patient_id' => $this->patient->id,
            '_who_added' => $this->admin->id,
        ]);

        $medicalService = MedicalService::create([
            'name' => 'Cleaning',
            'price' => 100,
            '_who_added' => $this->admin->id,
        ]);

        QuotationItem::create([
            'qty' => 2,
            'amount' => 50,
            'quotation_id' => $quotation->id,
            'medical_service_id' => $medicalService->id,
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/quotations?draw=1&start=0&length=10&columns[0][data]=DT_RowIndex', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test that the total_amount subquery calculates correctly.
     */
    public function test_quotation_total_amount_calculated_correctly(): void
    {
        $quotation = Quotation::create([
            'quotation_no' => 'Q-002',
            'patient_id' => $this->patient->id,
            '_who_added' => $this->admin->id,
        ]);

        $medicalService = MedicalService::create([
            'name' => 'Filling',
            'price' => 200,
            '_who_added' => $this->admin->id,
        ]);

        QuotationItem::create([
            'qty' => 3,
            'amount' => 200,
            'quotation_id' => $quotation->id,
            'medical_service_id' => $medicalService->id,
            '_who_added' => $this->admin->id,
        ]);

        QuotationItem::create([
            'qty' => 1,
            'amount' => 100,
            'quotation_id' => $quotation->id,
            'medical_service_id' => $medicalService->id,
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/quotations?draw=1&start=0&length=10&columns[0][data]=DT_RowIndex', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        // 3*200 + 1*100 = 700
        $this->assertEquals('700', $data[0]['amount']);
    }
}
