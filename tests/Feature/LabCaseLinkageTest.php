<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Lab;
use App\LabCase;
use App\MedicalCase;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 加工单要挂到具体就诊与病历上。
 *
 * lab_cases 表上一直有 appointment_id / medical_case_id 两列 —— 表是按「挂在就诊上」
 * 设计的，但 store() 从不接收这两个字段，也没有任何入口会带上，于是加工单是一张
 * 孤立的单子：「这次戴牙对应哪次取模」查不出来。
 *
 * 这组用例钉三件事：
 *   1. 带了关联 id 就落库
 *   2. 不带也能开（加工单页直接新建时没有就诊上下文）
 *   3. 关联的就诊/病历必须属于同一个患者 —— exists 只保证记录在，
 *      两个 id 各自独立，改一下请求就能把加工单挂到别人的就诊上
 */
class LabCaseLinkageTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;
    private Patient $other;
    private Appointment $appointment;
    private MedicalCase $case;
    private Lab $lab;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        foreach (['manage-labs', 'view-labs'] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => '技工管理']);
            RolePermission::firstOrCreate(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }

        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20261001', 'surname' => '韩', 'othername' => '十四',
            'gender' => 'Male', 'phone_no' => '13800138014', '_who_added' => $this->doctor->id,
        ]);
        $this->other = Patient::create([
            'patient_no' => '20261002', 'surname' => '杨', 'othername' => '十五',
            'gender' => 'Female', 'phone_no' => '13800138015', '_who_added' => $this->doctor->id,
        ]);

        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'branch_id' => $branch->id, 'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'), 'start_time' => '09:00', 'end_time' => '09:30',
            '_who_added' => $this->doctor->id,
        ]);

        $this->case = MedicalCase::create([
            'case_no' => MedicalCase::CaseNumber(), 'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id, 'case_date' => now()->format('Y-m-d'),
            'chief_complaint' => '需要做冠', 'status' => MedicalCase::STATUS_OPEN,
            '_who_added' => $this->doctor->id,
        ]);

        $this->lab = Lab::create([
            'name' => '示例加工厂', 'phone' => '021-00000000',
            'is_active' => true, '_who_added' => $this->doctor->id,
        ]);

        Cache::flush();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'lab_id'     => $this->lab->id,
            'items'      => [[
                'prosthesis_type' => '全瓷冠',
                'material'        => '二氧化锆',
                'teeth_positions' => '16',
                'qty'             => 1,
            ]],
        ], $overrides);
    }

    /** @test */
    public function 加工单能挂到就诊与病历上(): void
    {
        $this->actingAs($this->doctor)
            ->postJson('/lab-cases', $this->payload([
                'appointment_id'  => $this->appointment->id,
                'medical_case_id' => $this->case->id,
            ]))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $labCase = LabCase::latest('id')->first();

        $this->assertSame($this->appointment->id, (int) $labCase->appointment_id);
        $this->assertSame($this->case->id, (int) $labCase->medical_case_id);
    }

    /**
     * 从加工单页直接新建时没有就诊上下文，照样要能开。
     */
    /** @test */
    public function 不带关联也能开单(): void
    {
        $this->actingAs($this->doctor)
            ->postJson('/lab-cases', $this->payload())
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $labCase = LabCase::latest('id')->first();

        $this->assertNull($labCase->appointment_id);
        $this->assertNull($labCase->medical_case_id);
    }

    /** @test */
    public function 不能挂到别人的就诊上(): void
    {
        $othersAppointment = Appointment::create([
            'patient_id' => $this->other->id, 'doctor_id' => $this->doctor->id,
            'branch_id' => $this->appointment->branch_id, 'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'), 'start_time' => '10:00', 'end_time' => '10:30',
            '_who_added' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->postJson('/lab-cases', $this->payload(['appointment_id' => $othersAppointment->id]))
            ->assertStatus(422);

        $this->assertSame(0, LabCase::count());
    }

    /** @test */
    public function 不能挂到别人的病历上(): void
    {
        $othersCase = MedicalCase::create([
            'case_no' => MedicalCase::CaseNumber(), 'patient_id' => $this->other->id,
            'doctor_id' => $this->doctor->id, 'case_date' => now()->format('Y-m-d'),
            'chief_complaint' => '别人的病历', 'status' => MedicalCase::STATUS_OPEN,
            '_who_added' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->postJson('/lab-cases', $this->payload(['medical_case_id' => $othersCase->id]))
            ->assertStatus(422);

        $this->assertSame(0, LabCase::count());
    }
}
