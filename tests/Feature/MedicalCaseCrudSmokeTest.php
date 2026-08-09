<?php

namespace Tests\Feature;

use App\Branch;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MedicalCaseCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch    = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-medical-cases'],
            ['name' => 'Manage Medical Cases']
        );
        RolePermission::firstOrCreate([
            'role_id'       => $adminRole->id,
            'permission_id' => $permission->id,
        ]);
    }

    /** @test */
    public function medical_cases_datatable_returns_success(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/medical-cases?draw=1&start=0&length=10', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /** @test */
    public function doctor_can_save_a_medical_case_draft_without_create_patient_permission(): void
    {
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        foreach (['view-medical-cases', 'manage-medical-cases'] as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst(str_replace('-', ' ', $slug))]
            );
            RolePermission::create([
                'role_id'       => $doctorRole->id,
                'permission_id' => $permission->id,
            ]);
        }

        $doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => Branch::firstOrFail()->id,
            'is_doctor' => true,
        ]);
        $patient = Patient::create([
            'patient_no' => 'MC-DRAFT-001',
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $doctor->id,
        ]);

        $this->assertFalse(
            $doctorRole->hasPermission('create-patients'),
            '医生不应为了保存既有患者的病例而被迫获得新建患者权限'
        );

        $response = $this->actingAs($doctor)->postJson('/medical-cases', [
            'patient_id' => $patient->id,
            'case_date'  => now()->toDateString(),
            'is_draft'   => '1',
        ]);

        $response->assertOk()
            ->assertJson(['status' => true]);
        $this->assertDatabaseHas('medical_cases', [
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'is_draft'   => 1,
        ]);
    }
}
