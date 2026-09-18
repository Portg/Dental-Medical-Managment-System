<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\DentalChart;
use App\MedicalCase;
use App\Patient;
use App\Role;
use App\Services\DentalChartService;
use App\Services\MedicalCaseService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 病历里的牙位标记投影成牙位状态。
 *
 * dental_charts 存的是牙齿的**当前状态**，它该由历次临床观察派生 —— 而不是另开
 * 一个录入口让人手工维护。那张表此前长期只有 1 条记录，正是因为从来没有东西去
 * 喂它：医生在病历里写「45 残根」，牙位图上 45 还是 normal，于是「45 这颗牙历次
 * 做过什么」根本查不出来。
 */
class ToothStateProjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);
        Auth::login($this->doctor);

        $this->patient = Patient::create([
            'patient_no' => 'TP-1', 'surname' => '刘', 'othername' => '万友',
            'gender' => 'Male', 'phone_no' => '13800138041', '_who_added' => $this->doctor->id,
        ]);
    }

    private function service(): MedicalCaseService
    {
        return app(MedicalCaseService::class);
    }

    private function makeCase(): MedicalCase
    {
        return MedicalCase::create([
            'case_no'    => MedicalCase::CaseNumber(),
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'case_date'  => now()->format('Y-m-d'),
            'status'     => MedicalCase::STATUS_OPEN,
            '_who_added' => $this->doctor->id,
        ]);
    }

    private function save(MedicalCase $case, array $rows): void
    {
        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems($rows)['items']);
    }

    public function test_检查里的标记投影成牙位状态(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '45',
             'tooth_mark' => 'residual_root', 'content' => '冠部大面积缺损，仅存牙根'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id,
            'tooth_number'    => '45',
            'tooth_status'    => 'residual_root',
        ]);
    }

    /**
     * ✕ 与 — 都落到 missing：就当前状态而言，拔掉的和天生缺的没有区别；
     * 区别在于「怎么没的」，那是病历里记的事，不该塞进状态枚举。
     */
    public function test_已拔除与缺失都落到missing(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '38', 'tooth_mark' => 'extracted', 'content' => '已拔除'],
            ['section' => 'examination', 'tooth_no' => '36', 'tooth_mark' => 'missing',   'content' => '缺失'],
        ]);

        foreach (['38', '36'] as $tooth) {
            $this->assertDatabaseHas('dental_charts', [
                'medical_case_id' => $case->id, 'tooth_number' => $tooth, 'tooth_status' => 'missing',
            ]);
        }
    }

    /**
     * 治疗计划里的标记**不**投影。
     *
     * 治疗计划记的是「打算做什么」。医生在那里给一颗牙标 ✕ 表示「计划拔除」，
     * 那颗牙此刻还在嘴里 —— 投影成 missing 等于把打算当成既成事实，
     * 牙位图上会显示一颗根本没拔的牙已经没了。
     */
    public function test_治疗计划里的标记不投影(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'treatment_plan', 'tooth_no' => '18',
             'tooth_mark' => 'extracted', 'content' => '择期拔除'],
        ]);

        $this->assertDatabaseMissing('dental_charts', ['medical_case_id' => $case->id, 'tooth_number' => '18']);
    }

    /**
     * 同一颗牙在几段里都标了：以靠后的段为准。
     * 「这次拔掉了」应当盖过「检查时是残根」。
     */
    public function test_同一颗牙以靠后的段为准(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '46', 'tooth_mark' => 'residual_root', 'content' => '残根'],
            ['section' => 'treatment',   'tooth_no' => '46', 'tooth_mark' => 'extracted',     'content' => '拔除患牙'],
        ]);

        $this->assertSame(
            1,
            DentalChart::where('medical_case_id', $case->id)->where('tooth_number', '46')->count(),
            '一颗牙只该有一条状态'
        );
        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id, 'tooth_number' => '46', 'tooth_status' => 'missing',
        ]);
    }

    /**
     * 医生把标记改掉或去掉时，旧的投影必须跟着消失 ——
     * 否则牙位图上会留下一个永远撤不掉的状态。
     */
    public function test_改掉标记后旧投影跟着撤销(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '45', 'tooth_mark' => 'residual_root', 'content' => '残根'],
        ]);
        $this->assertSame(1, DentalChart::where('medical_case_id', $case->id)->count());

        // 医生把标记去掉了
        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '45', 'tooth_mark' => '', 'content' => '龋坏'],
        ]);

        $this->assertSame(0, DentalChart::where('medical_case_id', $case->id)->count());
    }

    /**
     * 没有牙位标记的行不投影 —— 大多数病历行只是文字描述，不是状态断言。
     */
    public function test_没有标记的行不投影(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '16', 'tooth_mark' => '', 'content' => '龋坏'],
        ]);

        $this->assertSame(0, DentalChart::where('medical_case_id', $case->id)->count());
    }

    /**
     * 患者的牙位状态汇总要能看见病历投出来的记录。
     *
     * 原来那个查询是 inner join appointments 找患者的 —— 只挂 medical_case_id
     * 没挂 appointment_id 的记录完全隐形，而从患者页建的病历正是这种。
     * 漏掉不会报错，只是牙位图上少几颗牙的状态，最难发现。
     */
    public function test_汇总看得见没有就诊的病历投影(): void
    {
        $case = $this->makeCase();     // 刻意不建预约

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '45', 'tooth_mark' => 'residual_root', 'content' => '残根'],
        ]);

        $summary = app(DentalChartService::class)->getChartSummaryForPatient($this->patient->id);

        $this->assertNotEmpty($summary, '没有就诊的病历投影不该对汇总隐形');
        $this->assertStringContainsString('45', json_encode($summary, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 有就诊时也要带上 appointment_id —— 牙位图那条老路是按就诊查的。
     */
    public function test_有就诊时投影带上就诊id(): void
    {
        $case = $this->makeCase();

        $appointment = Appointment::create([
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->doctor->id,
            'start_date'      => now()->format('Y-m-d'),
            'medical_case_id' => $case->id,
            '_who_added'      => $this->doctor->id,
        ]);

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '45', 'tooth_mark' => 'residual_root', 'content' => '残根'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id,
            'appointment_id'  => $appointment->id,
        ]);
    }
}
