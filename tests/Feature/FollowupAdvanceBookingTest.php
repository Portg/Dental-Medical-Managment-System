<?php

namespace Tests\Feature;

use App\Branch;
use App\Patient;
use App\PatientFollowup;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\SystemSetting;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * clinic.max_advance_days（默认 7 天）本意是防散客把远期的号占满。
 * 但医生写「两周后复诊」是牙科最常见的间隔之一，前台在病人离店时按那个日期约，
 * 会被这条规则直接挡掉 —— 浏览器实测报「最多只能提前 7 天预约」。
 *
 * 这组用例刻意走 HTTP 端点而不是直接调 AppointmentService：那道校验在控制器里，
 * 上一版的单测直接调 Service，全绿却掩盖了这个问题。
 */
class FollowupAdvanceBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Receptionist', 'slug' => 'receptionist']);

        foreach (['view-appointments', 'create-appointments'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            RolePermission::firstOrCreate([
                'role_id'       => $role->id,
                'permission_id' => $permission->id,
            ]);
        }

        $this->staff = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => true,
        ]);
        $this->actingAs($this->staff);

        $this->patient = Patient::create([
            'patient_no' => 'AB-' . uniqid(),
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->staff->id,
        ]);

        SystemSetting::updateOrCreate(['key' => 'clinic.max_advance_days'], ['value' => '7']);
    }

    private function farDate(): string
    {
        return now()->addDays(14)->toDateString();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->staff->id,
            'appointment_date'  => $this->farDate(),
            'appointment_time'  => '10:00',
            'visit_information' => 'Revisit',
        ], $overrides);
    }

    private function pendingFollowup(?int $patientId = null): PatientFollowup
    {
        return PatientFollowup::create([
            'followup_no'    => PatientFollowup::generateFollowupNo(),
            'followup_type'  => 'Visit',
            'scheduled_date' => $this->farDate(),
            'status'         => PatientFollowup::STATUS_PENDING,
            'purpose'        => '拆线',
            'patient_id'     => $patientId ?? $this->patient->id,
            '_who_added'     => $this->staff->id,
        ]);
    }

    /** @test */
    public function a_plain_booking_beyond_the_window_is_still_rejected(): void
    {
        $this->postJson('/appointments', $this->payload())
            ->assertOk()
            ->assertJson(['status' => false]);
    }

    /** @test */
    public function a_followup_driven_booking_is_exempt_from_the_max_advance_window(): void
    {
        $followup = $this->pendingFollowup();

        $response = $this->postJson('/appointments', $this->payload([
            'followup_id' => $followup->id,
        ]));

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame(
            PatientFollowup::STATUS_COMPLETED,
            $followup->refresh()->status,
            '豁免放行之后，那条待办也应当场闭环'
        );
    }

    /**
     * followup_id 是前端带来的，不能成为绕过提前预约限制的后门。
     */
    /** @test */
    public function a_followup_belonging_to_someone_else_does_not_grant_the_exemption(): void
    {
        $other = Patient::create([
            'patient_no' => 'AB-' . uniqid(),
            'surname'    => '另一',
            'othername'  => '患者',
            'gender'     => 'Female',
            'phone_no'   => '13900139000',
            '_who_added' => $this->staff->id,
        ]);

        $this->postJson('/appointments', $this->payload([
            'followup_id' => $this->pendingFollowup($other->id)->id,
        ]))->assertOk()->assertJson(['status' => false]);
    }

    /** @test */
    public function an_unknown_followup_id_does_not_grant_the_exemption(): void
    {
        $this->postJson('/appointments', $this->payload(['followup_id' => 999999]))
            ->assertOk()
            ->assertJson(['status' => false]);
    }
}
