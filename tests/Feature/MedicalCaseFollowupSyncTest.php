<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalCase;
use App\Patient;
use App\PatientFollowup;
use App\Services\MedicalCaseService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 病例里的「下次复诊日期」要变成一条随访待办，否则那个字段没有下游、等于装饰。
 *
 * 刻意写进 patient_followups 而不是 appointments：后者会被三个仪表盘的
 * Appointment::today()->count() 算成「今日预约数」，而复诊建议既没时间也没通知病人。
 */
class MedicalCaseFollowupSyncTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = \App\Role::first() ?: \App\Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $user = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => true,
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function patient(User $doctor): Patient
    {
        return Patient::create([
            'patient_no' => 'FU-' . uniqid(),
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $doctor->id,
        ]);
    }

    private function service(): MedicalCaseService
    {
        return app(MedicalCaseService::class);
    }

    private function caseData(Patient $patient, User $doctor, ?string $nextVisit, bool $wantsFollowup = true): array
    {
        return [
            'patient_id'           => $patient->id,
            'doctor_id'            => $doctor->id,
            'case_date'            => now()->toDateString(),
            'next_visit_date'      => $nextVisit,
            'next_visit_note'      => $nextVisit ? '拆线' : null,
            // 界面上默认勾选：写了复诊日期正常就该有人跟进
            'auto_create_followup' => $wantsFollowup,
            '_who_added'           => $doctor->id,
        ];
    }

    /** @test */
    public function submitting_a_case_with_a_next_visit_date_creates_one_pending_followup(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            false
        );

        $this->assertNotNull($case);
        $followups = PatientFollowup::where('medical_case_id', $case->id)->get();

        $this->assertCount(1, $followups, '提交病例应生成一条复诊待办');
        $this->assertSame(PatientFollowup::STATUS_PENDING, $followups[0]->status);
        $this->assertSame($patient->id, $followups[0]->patient_id);
        $this->assertSame('Visit', $followups[0]->followup_type, '到店复诊应是 Visit，不是默认的 Phone');
        $this->assertSame('2026-09-01', $followups[0]->scheduled_date->format('Y-m-d'));
        $this->assertSame('拆线', $followups[0]->purpose);
    }

    /** @test */
    public function draft_cases_do_not_create_followups(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            true
        );

        $this->assertSame(
            0,
            PatientFollowup::where('medical_case_id', $case->id)->count(),
            '草稿会反复保存，不该每次都留下待办'
        );
    }

    /**
     * 草稿阶段反复改日期不该留下任何待办，提交时只按最后那个日期建一条。
     */
    /** @test */
    public function editing_a_draft_repeatedly_then_submitting_leaves_exactly_one_followup(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            true
        );

        foreach (['2026-09-08', '2026-09-15'] as $newDate) {
            $this->service()->updateCase(
                $case->id,
                $this->caseData($patient, $doctor, $newDate),
                true
            );
        }
        $this->assertSame(0, PatientFollowup::where('medical_case_id', $case->id)->count());

        // 正式提交
        $this->service()->updateCase(
            $case->id,
            $this->caseData($patient, $doctor, '2026-09-15'),
            false
        );

        $followups = PatientFollowup::where('medical_case_id', $case->id)->get();
        $this->assertCount(1, $followups, '草稿改多少次，提交后都只该有一条待办');
        $this->assertSame('2026-09-15', $followups[0]->scheduled_date->format('Y-m-d'));
    }

    /**
     * 病例一提交就锁定，之后改复诊日期只能走修改申请。审批通过是直接
     * fill()->save() 的，不经过 Service —— 待办必须跟着走，否则会停在旧日期。
     */
    /** @test */
    public function an_approved_amendment_moves_the_followup_instead_of_creating_another(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            false
        );

        $amendment = $this->service()->createAmendment(
            $case->refresh(),
            ['next_visit_date' => '2026-10-20'],
            '患者出差改期'
        );
        $amendment->approve($doctor->id);

        $followups = PatientFollowup::where('medical_case_id', $case->id)->get();
        $this->assertCount(1, $followups, '改期只应移动原待办，不是再建一条');
        $this->assertSame('2026-10-20', $followups[0]->scheduled_date->format('Y-m-d'));
    }

    /** @test */
    public function clearing_the_date_through_an_amendment_cancels_the_pending_followup(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            false
        );

        $amendment = $this->service()->createAmendment(
            $case->refresh(),
            ['next_visit_date' => null],
            '无需复诊'
        );
        $amendment->approve($doctor->id);

        $followup = PatientFollowup::where('medical_case_id', $case->id)->first();

        $this->assertNotNull($followup);
        $this->assertSame(
            PatientFollowup::STATUS_CANCELLED,
            $followup->status,
            '医嘱撤回后待办要取消，不能留孤儿'
        );
    }

    /** @test */
    public function an_already_completed_followup_is_left_alone(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01'),
            false
        );

        // 前台已经把它约成预约了
        PatientFollowup::where('medical_case_id', $case->id)
            ->update(['status' => PatientFollowup::STATUS_COMPLETED]);

        $this->service()->updateCase(
            $case->id,
            $this->caseData($patient, $doctor, '2026-09-20'),
            false
        );

        $completed = PatientFollowup::where('medical_case_id', $case->id)
            ->where('status', PatientFollowup::STATUS_COMPLETED)
            ->first();

        $this->assertNotNull($completed);
        $this->assertSame(
            '2026-09-01',
            $completed->scheduled_date->format('Y-m-d'),
            '已约掉的待办不该被病例改动倒回去'
        );
    }

    /**
     * 条件性复诊：「若症状持续，两周后复诊」是写给病人和病历看的，
     * 不该让前台去打召回电话。取消勾选就只留记录。
     */
    /** @test */
    public function unchecking_the_followup_box_records_the_date_without_creating_a_task(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01', false),
            false
        );

        $this->assertSame(
            '2026-09-01',
            $case->refresh()->next_visit_date->format('Y-m-d'),
            '日期本身仍要记进病历'
        );
        $this->assertSame(
            0,
            PatientFollowup::where('medical_case_id', $case->id)->count(),
            '没勾就不该进前台的跟进列表'
        );
    }

    /** @test */
    public function unchecking_it_later_cancels_the_task_that_was_already_created(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $case = $this->service()->createCase(
            $this->caseData($patient, $doctor, '2026-09-01', true),
            false
        );
        $this->assertSame(1, PatientFollowup::where('medical_case_id', $case->id)->count());

        $amendment = $this->service()->createAmendment(
            $case->refresh(),
            ['auto_create_followup' => false],
            '改为按需复诊'
        );
        $amendment->approve($doctor->id);

        $this->assertSame(
            PatientFollowup::STATUS_CANCELLED,
            PatientFollowup::where('medical_case_id', $case->id)->first()->status,
            '改成不跟进后，已建的待办要撤销'
        );
    }

    /** @test */
    public function followups_never_land_in_the_appointments_table(): void
    {
        $doctor  = $this->actor();
        $patient = $this->patient($doctor);

        $before = \App\Appointment::count();

        $this->service()->createCase(
            $this->caseData($patient, $doctor, now()->toDateString()),
            false
        );

        $this->assertSame(
            $before,
            \App\Appointment::count(),
            '复诊建议进 appointments 会污染三个仪表盘的「今日预约数」'
        );
    }
}
