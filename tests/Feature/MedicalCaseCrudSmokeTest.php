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

    /**
     * 病历打印取数不能炸。
     *
     * getPrintData() 原先按 VitalSign::where('medical_case_id', ...) 查最近一次
     * 体征，而 vital_signs 表根本没有这一列（体征是按患者/预约记录的）——
     * SQL 直接抛 Unknown column，也就是说 /print-medical-case/{id} 从来没有
     * 成功过一次。页面上看不出来，只有真去打印才发现。
     */
    /** @test */
    public function print_data_can_be_assembled_for_a_medical_case(): void
    {
        $patient = Patient::create([
            'patient_no' => 'MC-PRINT-001',
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138001',
            '_who_added' => $this->admin->id,
        ]);

        $case = \App\MedicalCase::create([
            'case_no'    => 'MC-PRINT-0001',
            'patient_id' => $patient->id,
            'doctor_id'  => $this->admin->id,
            'case_date'  => now()->toDateString(),
            '_who_added' => $this->admin->id,
        ]);

        // 该患者的体征按 patient_id 记录，打印时应取到它
        \App\VitalSign::create([
            'patient_id'  => $patient->id,
            'temperature' => 36.7,
            'heart_rate'  => 72,
            'recorded_at' => now(),
            '_who_added'  => $this->admin->id,
        ]);

        $data = app(\App\Services\MedicalCaseService::class)->getPrintData($case->id);

        $this->assertSame($case->id, $data['case']->id);
        $this->assertNotNull($data['latestVitalSign'], '按 patient_id 应能取到该患者的体征');
        $this->assertEquals(72, $data['latestVitalSign']->heart_rate);
    }
}
