<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Lab;
use App\LabCase;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 技加工诊所动线：患者页入口、工作台签收、诊疗开单带医生。
 */
class LabCaseClinicFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $doctor;
    private Patient $patient;
    private Lab $lab;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin-lab-flow']);

        foreach (['manage-labs', 'view-labs', 'view-patients', 'edit-patients', 'view-appointments'] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => '技工']);
            RolePermission::firstOrCreate(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
            'surname' => '关', 'othername' => '立亚',
        ]);
        $this->patient = Patient::create([
            'patient_no' => 'LAB-' . uniqid(),
            'surname' => '张', 'othername' => '工',
            'gender' => 'Male', 'phone_no' => '13900001111',
            '_who_added' => $this->staff->id,
        ]);
        $this->lab = Lab::create([
            'name' => '测试技工厂', 'is_active' => true, '_who_added' => $this->staff->id,
        ]);
        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'branch_id' => $branch->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'sort_by' => now()->format('Y-m-d') . ' 10:00:00',
            '_who_added' => $this->staff->id,
        ]);

        Cache::flush();
    }

    /** @test */
    public function 患者详情页挂了技工单Tab与新建弹窗(): void
    {
        $html = $this->actingAs($this->staff)
            ->get('/patients/' . $this->patient->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="lab_cases_tab"', $html);
        $this->assertStringContainsString('href="#lab_cases_tab"', $html);
        $this->assertStringContainsString('id="addPatientLabCaseModal"', $html);
        $this->assertStringContainsString('createPatientLabCase()', $html);
    }

    /** @test */
    public function 诊疗页开加工单链接带接诊医生(): void
    {
        $html = $this->actingAs($this->staff)
            ->get('/medical-treatment/' . $this->appointment->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('doctor_id=' . $this->doctor->id, $html);
        $this->assertStringContainsString('appointment_id=' . $this->appointment->id, $html);
    }

    /** @test */
    public function 工作台外加工列表含id且可签收(): void
    {
        $case = LabCase::create([
            'lab_case_no' => 'LC' . date('Ymd') . '9901',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'lab_id' => $this->lab->id,
            'status' => LabCase::STATUS_SENT,
            'expected_return_date' => now()->format('Y-m-d'),
            '_who_added' => $this->staff->id,
        ]);

        $rows = $this->actingAs($this->staff)
            ->getJson('/today-work/lab-cases?date=' . now()->format('Y-m-d'))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($rows);
        $this->assertSame($case->id, $rows[0]['id']);
        $this->assertSame('sent', $rows[0]['status']);

        $this->actingAs($this->staff)
            ->postJson('/lab-cases/' . $case->id . '/update-status', ['status' => 'returned'])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertSame(LabCase::STATUS_RETURNED, $case->fresh()->status);
        $this->assertNotNull($case->fresh()->actual_return_date);
    }

    /** @test */
    public function 新建技工单必须带items明细(): void
    {
        $ok = $this->actingAs($this->staff)->postJson('/lab-cases', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'lab_id' => $this->lab->id,
            'appointment_id' => $this->appointment->id,
            'items' => [[
                'prosthesis_type' => 'crown',
                'material' => 'zirconia',
                'teeth_positions' => '16',
                'qty' => 1,
            ]],
        ]);
        $ok->assertOk()->assertJsonPath('status', true);
        $this->assertDatabaseHas('lab_cases', [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
        ]);

        $flat = $this->actingAs($this->staff)->postJson('/lab-cases', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'lab_id' => $this->lab->id,
            'prosthesis_type' => 'crown',
        ]);
        $flat->assertOk();
        $this->assertFalse($flat->json('status'));
    }
}
