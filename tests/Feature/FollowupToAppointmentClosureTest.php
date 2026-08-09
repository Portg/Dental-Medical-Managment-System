<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\PatientFollowup;
use App\Services\AppointmentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * 前台在病人离店时用「约下次」约上复诊后，那条待办必须当场闭环。
 *
 * 不闭环的话，复诊日当天它还会跳进随访列表让人再打一次电话 ——
 * 病人接到「该来复诊了」，回一句「我不是已经约了吗」。
 */
class FollowupToAppointmentClosureTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // 建预约会 dispatch 短信任务。测试里队列是同步的，而 sms_loggings 表
        // 缺 type 列（见 SmsLogger::LogSms）—— 那是另一个独立的既有 bug，
        // 不该把它拖进这组用例。与 MedicalCaseCrudSmokeTest 的做法一致。
        Bus::fake();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = \App\Role::first() ?: \App\Role::create(['name' => 'Receptionist', 'slug' => 'receptionist']);

        $this->staff = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => true,
        ]);
        $this->actingAs($this->staff);

        $this->patient = Patient::create([
            'patient_no' => 'CL-' . uniqid(),
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->staff->id,
        ]);
    }

    private function pendingFollowup(?Patient $patient = null): PatientFollowup
    {
        return PatientFollowup::create([
            'followup_no'    => PatientFollowup::generateFollowupNo(),
            'followup_type'  => 'Visit',
            'scheduled_date' => '2026-09-15',
            'status'         => PatientFollowup::STATUS_PENDING,
            'purpose'        => '拆线',
            'patient_id'     => ($patient ?? $this->patient)->id,
            '_who_added'     => $this->staff->id,
        ]);
    }

    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->staff->id,
            'appointment_date'  => '2026-09-15',
            'appointment_time'  => '10:00',
            'visit_information' => '复诊',
        ], $overrides);
    }

    /** @test */
    public function booking_with_a_followup_id_closes_that_followup_and_links_the_appointment(): void
    {
        $followup = $this->pendingFollowup();

        $appointment = app(AppointmentService::class)->createAppointment(
            $this->bookingData(['followup_id' => $followup->id])
        );

        $this->assertNotNull($appointment);
        $followup->refresh();

        $this->assertSame(PatientFollowup::STATUS_COMPLETED, $followup->status);
        $this->assertSame($appointment->id, $followup->appointment_id, 'appointment_id 必须回填，否则无从知道约到了哪一条');
        $this->assertNotNull($followup->completed_date);
    }

    /** @test */
    public function a_normal_booking_without_followup_id_touches_no_followup(): void
    {
        $followup = $this->pendingFollowup();

        app(AppointmentService::class)->createAppointment($this->bookingData());

        $this->assertSame(
            PatientFollowup::STATUS_PENDING,
            $followup->refresh()->status,
            '普通建约不该顺手把别的待办标成完成'
        );
    }

    /**
     * followup_id 是前端带来的，不能直接信：只认自己患者名下、且仍是 Pending 的那条。
     */
    /** @test */
    public function a_followup_belonging_to_another_patient_is_not_closed(): void
    {
        $otherPatient = Patient::create([
            'patient_no' => 'CL-' . uniqid(),
            'surname'    => '另一',
            'othername'  => '患者',
            'gender'     => 'Female',
            'phone_no'   => '13900139000',
            '_who_added' => $this->staff->id,
        ]);
        $foreign = $this->pendingFollowup($otherPatient);

        app(AppointmentService::class)->createAppointment(
            $this->bookingData(['followup_id' => $foreign->id])
        );

        $this->assertSame(
            PatientFollowup::STATUS_PENDING,
            $foreign->refresh()->status,
            '别的患者的待办不能被这次预约关掉'
        );
        $this->assertNull($foreign->appointment_id);
    }

    /** @test */
    public function an_already_completed_followup_is_not_relinked(): void
    {
        $followup = $this->pendingFollowup();
        $firstAppointment = Appointment::create([
            'appointment_no'    => Appointment::AppointmentNo(),
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->staff->id,
            'start_date'        => '2026-09-15',
            'end_date'          => '2026-09-15',
            'start_time'        => '09:00',
            'visit_information' => '复诊',
            'branch_id'         => $this->staff->branch_id,
            'sort_by'           => '2026-09-15 09:00:00',
            '_who_added'        => $this->staff->id,
        ]);
        $followup->update([
            'status'         => PatientFollowup::STATUS_COMPLETED,
            'appointment_id' => $firstAppointment->id,
        ]);

        app(AppointmentService::class)->createAppointment(
            $this->bookingData(['appointment_time' => '11:00', 'followup_id' => $followup->id])
        );

        $this->assertSame(
            $firstAppointment->id,
            $followup->refresh()->appointment_id,
            '已闭环的待办不该被后来的预约改指向'
        );
    }
}
