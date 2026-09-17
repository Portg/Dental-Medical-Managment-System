<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use App\WaitingQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * 工作台「就诊流程」列：单列下一步下拉（对齐轻松牙医）。
 *
 * 合上时显示当前主操作；展开含同状态次要动作（回退/失约等）。
 * 病历 / 收费不进此下拉。
 */
class TodayWorkNextStepDropdownTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $permIds = [];
        foreach (['view-appointments', 'create-appointments', 'view-medical-cases', 'manage-medical-cases', 'create-invoices'] as $slug) {
            $permIds[] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '预约管理']
            )->id;
        }

        $role = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk-flow']);
        foreach ($permIds as $permId) {
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $permId]);
        }

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->patient = Patient::create([
            'patient_no' => 'FLOW-' . uniqid(),
            'surname'    => '张',
            'othername'  => '志伟',
            'gender'     => 'Male',
            'phone_no'   => '13900139001',
            '_who_added' => $this->staff->id,
        ]);
    }

    private function makeAppointment(string $aptStatus = Appointment::STATUS_SCHEDULED): Appointment
    {
        return Appointment::create([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $this->staff->branch_id,
            'start_date'        => now()->format('Y-m-d'),
            'end_date'          => now()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'sort_by'           => now()->format('Y-m-d') . ' 10:00:00',
            'status'            => $aptStatus,
            'visit_information' => Appointment::VISIT_APPOINTMENT,
            '_who_added'        => $this->staff->id,
        ]);
    }

    private function rowForToday(): array
    {
        return $this->actingAs($this->staff)
            ->getJson('/today-work/data?status=all&date=' . now()->format('Y-m-d'))
            ->assertOk()
            ->json('data.0');
    }

    /** @test */
    public function 未到显示挂号下拉并含失约(): void
    {
        $this->makeAppointment();

        $row = $this->rowForToday();
        $html = $row['act_flow'];

        $this->assertStringContainsString('tw-next-dd', $html);
        $this->assertStringContainsString('quickCheckIn', $html);
        $this->assertStringContainsString(__('today_work.check_in'), $html);
        $this->assertStringContainsString('quickNoShow', $html);
        $this->assertStringNotContainsString('quickMedicalCase', $html);
        $this->assertStringContainsString('quickMedicalCase', $row['act_case']);
    }

    /** @test */
    public function 候诊显示叫号下拉并含回退(): void
    {
        $apt = $this->makeAppointment(Appointment::STATUS_CHECKED_IN);
        WaitingQueue::create([
            'appointment_id' => $apt->id,
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctor->id,
            'branch_id'      => $this->staff->branch_id,
            'status'         => WaitingQueue::STATUS_WAITING,
            'check_in_time'  => now(),
            'created_by'     => $this->staff->id,
        ]);

        $html = $this->rowForToday()['act_flow'];

        $this->assertStringContainsString('quickCall', $html);
        $this->assertStringContainsString(__('today_work.call'), $html);
        $this->assertStringContainsString('quickRollback', $html);
        $this->assertStringContainsString(__('today_work.rollback'), $html);
    }

    /** @test */
    public function 治疗中显示完成治疗(): void
    {
        $apt = $this->makeAppointment(Appointment::STATUS_CHECKED_IN);
        WaitingQueue::create([
            'appointment_id' => $apt->id,
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctor->id,
            'branch_id'      => $this->staff->branch_id,
            'status'         => WaitingQueue::STATUS_IN_TREATMENT,
            'check_in_time'  => now(),
            'created_by'     => $this->staff->id,
        ]);

        $html = $this->rowForToday()['act_flow'];

        $this->assertStringContainsString('quickCompleteTreatment', $html);
        $this->assertStringContainsString(__('today_work.complete_treatment'), $html);
        $this->assertStringContainsString('quickRollback', $html);
    }

    /** @test */
    public function 已离开合上显示终态文案展开才是回退(): void
    {
        $apt = $this->makeAppointment(Appointment::STATUS_COMPLETED);
        WaitingQueue::create([
            'appointment_id' => $apt->id,
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctor->id,
            'branch_id'      => $this->staff->branch_id,
            'status'         => WaitingQueue::STATUS_COMPLETED,
            'check_in_time'  => now(),
            'created_by'     => $this->staff->id,
        ]);

        $html = $this->rowForToday()['act_flow'];

        $this->assertStringContainsString('>' . __('today_work.completed') . '</option>', $html);
        $this->assertStringContainsString('value="" selected disabled hidden', $html);
        $this->assertStringContainsString('quickRollback', $html);
        $this->assertStringNotContainsString(
            'value="" selected disabled hidden>' . __('today_work.rollback'),
            $html
        );
    }

    /** @test */
    public function 列表页列头是就诊流程(): void
    {
        $html = $this->actingAs($this->staff)->get('/today-work')->assertOk()->getContent();

        $this->assertStringContainsString(__('today_work.col_flow'), $html);
        $this->assertSame('就诊流程', __('today_work.col_flow'));
    }
}
