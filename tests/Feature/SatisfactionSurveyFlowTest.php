<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\SatisfactionSurvey;
use App\Services\SatisfactionSurveyService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 满意度调查闭环：生成链接 → 患者公开填写 → 提交 → 不可重复提交。
 *
 * 短信通道未接入，闭环靠 token 链接（前台复制后人工分发 / 现场平板填写），
 * 因此这条链路必须能独立跑通，且 token 的安全边界要立得住。
 */
class SatisfactionSurveyFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260801',
            'surname'    => '李',
            'othername'  => '四',
            'gender'     => 'Male',
            'phone_no'   => '13800138001',
            '_who_added' => $this->admin->id,
        ]);

        $this->appointment = Appointment::create([
            'start_date'        => now()->format('Y-m-d'),
            'end_date'          => now()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->admin->id,
            'branch_id'         => $branch->id,
            'status'            => Appointment::STATUS_COMPLETED,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->format('Y-m-d') . ' 10:00:00',
        ]);
    }

    private function service(): SatisfactionSurveyService
    {
        return app(SatisfactionSurveyService::class);
    }

    // ── 批量生成 ────────────────────────────────────────────────────

    public function test_send_batch_creates_surveys_with_tokens(): void
    {
        $this->actingAs($this->admin);

        $created = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat');

        $this->assertCount(1, $created);
        $survey = $created[0];

        $this->assertNotEmpty($survey->token);
        $this->assertSame(32, strlen($survey->token));
        // doctor_id 此前被误写成 $appointment->doctor（那是关联不是字段）
        $this->assertSame($this->admin->id, $survey->doctor_id);
        $this->assertSame($this->patient->id, $survey->patient_id);
        $this->assertSame(SatisfactionSurvey::STATUS_PENDING, $survey->status);
        $this->assertNotNull($survey->expires_at);
    }

    public function test_send_batch_skips_appointments_that_already_have_a_survey(): void
    {
        $this->actingAs($this->admin);

        $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat');
        $second = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat');

        $this->assertCount(0, $second, '同一预约不应重复生成问卷');
    }

    public function test_send_batch_ignores_non_completed_appointments(): void
    {
        $this->actingAs($this->admin);
        $this->appointment->update(['status' => Appointment::STATUS_WAITING]);

        $created = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat');

        $this->assertCount(0, $created);
    }

    // ── 患者公开填写 ────────────────────────────────────────────────

    public function test_patient_can_open_fill_page_without_login(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];

        // 显式登出，模拟患者在微信里点开链接
        auth()->logout();

        $this->get('/survey/' . $survey->token)
            ->assertStatus(200)
            ->assertSee(__('satisfaction.fill_title'));
    }

    public function test_patient_can_submit_and_survey_is_marked_completed(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        auth()->logout();

        $this->postJson('/survey/' . $survey->token, [
            'overall_rating'  => 5,
            'doctor_rating'   => 4,
            'would_recommend' => 9,
            'feedback'        => '医生很耐心',
        ])->assertStatus(200)->assertJsonPath('status', 1);

        $survey->refresh();
        $this->assertSame(SatisfactionSurvey::STATUS_COMPLETED, $survey->status);
        $this->assertSame(5, (int) $survey->overall_rating);
        $this->assertSame(9, (int) $survey->would_recommend);
        $this->assertNotNull($survey->survey_date);
    }

    public function test_overall_rating_is_required(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        auth()->logout();

        $this->postJson('/survey/' . $survey->token, ['feedback' => '只写了文字'])
            ->assertStatus(200)
            ->assertJsonPath('status', 0);

        $this->assertSame(SatisfactionSurvey::STATUS_PENDING, $survey->refresh()->status);
    }

    // ── 安全边界 ────────────────────────────────────────────────────

    public function test_completed_survey_cannot_be_submitted_twice(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        auth()->logout();

        $token = $survey->token;
        $this->postJson('/survey/' . $token, ['overall_rating' => 5])->assertStatus(200);

        // 链接泄露也不能覆盖已有评价
        $this->postJson('/survey/' . $token, ['overall_rating' => 1])->assertStatus(404);

        $this->assertSame(5, (int) $survey->refresh()->overall_rating);
    }

    public function test_expired_link_is_rejected_and_marked_expired(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        auth()->logout();

        $survey->update(['expires_at' => now()->subDay()]);

        $this->get('/survey/' . $survey->token)->assertStatus(404);
        $this->assertSame(SatisfactionSurvey::STATUS_EXPIRED, $survey->refresh()->status);
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->get('/survey/' . str_repeat('a', 32))->assertStatus(404);
    }

    public function test_token_is_not_exposed_in_model_serialization(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];

        // token 是患者侧唯一凭证，不应随任何 JSON 输出泄露
        $this->assertArrayNotHasKey('token', $survey->toArray());
    }

    // ── 重新生成链接 ────────────────────────────────────────────────

    public function test_regenerate_token_invalidates_the_old_link(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        $oldToken = $survey->token;

        $refreshed = $this->service()->regenerateToken($survey->id);

        $this->assertNotSame($oldToken, $refreshed->token);
        $this->get('/survey/' . $oldToken)->assertStatus(404);
        $this->get('/survey/' . $refreshed->token)->assertStatus(200);
    }

    public function test_regenerate_is_rejected_for_completed_survey(): void
    {
        $this->actingAs($this->admin);
        $survey = $this->service()->sendBatch(now()->format('Y-m-d'), 'wechat')[0];
        $survey->update(['status' => SatisfactionSurvey::STATUS_COMPLETED]);

        $this->expectException(\RuntimeException::class);
        $this->service()->regenerateToken($survey->id);
    }
}
