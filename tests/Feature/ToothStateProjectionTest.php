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
     * 治疗计划里的 ✕ 永远不会落成 missing。
     *
     * 这条用例原来断言的是「治疗计划整段不投影」—— 那是当时唯一安全的做法：
     * 能落的只有 missing，而那颗牙此刻还在嘴里，投成 missing 等于把打算当成
     * 既成事实，牙位图上会显示一颗根本没拔的牙已经没了。
     *
     * 现在它落到 extraction_planned（见 PLAN_MARK_TO_STATUS），信息不再被整个
     * 丢掉。但当初那个担心一步都不能松：这里守的就是「绝不是 missing」。
     */
    public function test_治疗计划的叉绝不落成缺失(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'treatment_plan', 'tooth_no' => '18',
             'tooth_mark' => 'extracted', 'content' => '择期拔除'],
        ]);

        $this->assertDatabaseMissing('dental_charts', [
            'medical_case_id' => $case->id, 'tooth_number' => '18', 'tooth_status' => 'missing',
        ]);
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
     * 治疗计划里的 ✕ 投影成「计划拔除」，不是「缺失」。
     *
     * 这一段原来整个不投影 —— 不是不想记，是当时没有合适的状态可落：投影成
     * missing 等于把打算当成既成事实，牙位图上会显示一颗根本没拔的牙已经没了。
     * extraction_planned 这个枚举值存在的意义恰好就是它，接上之后「计划拔除」
     * 这条信息不再被整个丢掉。
     */
    public function test_治疗计划的叉投影成计划拔除(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'treatment_plan', 'tooth_no' => '18', 'tooth_mark' => 'extracted', 'content' => '择期拔除'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id,
            'tooth_number'    => '18',
            'tooth_status'    => 'extraction_planned',
        ]);
    }

    /**
     * 治疗计划里的 △ 和 — 不投影。
     *
     * 那两个符号说的是牙**现在**什么样（残根/缺失），是观察不是计划。写在治疗
     * 计划段里只是复述检查段已经记过的事实，再投一次没有新信息，还会让「这条
     * 状态是从哪来的」变糊涂。只有 ✕ 在这一段里带计划语义。
     */
    public function test_治疗计划里只有叉投影(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'treatment_plan', 'tooth_no' => '45', 'tooth_mark' => 'residual_root', 'content' => '残根待处理'],
            ['section' => 'treatment_plan', 'tooth_no' => '36', 'tooth_mark' => 'missing',       'content' => '缺失待修复'],
        ]);

        $this->assertSame(0, DentalChart::where('medical_case_id', $case->id)->count());
    }

    /** 检查段的 ✕ 仍然是「已经不在」，不受上面那条影响。 */
    public function test_检查段的叉仍投影成缺失(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'tooth_no' => '38', 'tooth_mark' => 'extracted', 'content' => '已拔除'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id, 'tooth_number' => '38', 'tooth_status' => 'missing',
        ]);
    }

    /**
     * 同一颗牙：检查标残根、治疗计划标计划拔除 —— 以靠后的段为准。
     *
     * 两件事同时为真（冠没了，且打算拔掉），但牙位图一颗牙只显示一个状态。
     * 沿用既有规则「靠后的段盖过靠前的」：计划拔除是更可操作的那条，
     * 残根这个细节在病历文字里留着。
     */
    public function test_检查残根与计划拔除并存时以计划为准(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination',    'tooth_no' => '45', 'tooth_mark' => 'residual_root', 'content' => '残根'],
            ['section' => 'treatment_plan', 'tooth_no' => '45', 'tooth_mark' => 'extracted',     'content' => '择期拔除'],
        ]);

        $this->assertSame(1, DentalChart::where('medical_case_id', $case->id)->where('tooth_number', '45')->count());
        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id, 'tooth_number' => '45', 'tooth_status' => 'extraction_planned',
        ]);
    }

    /**
     * 象限码不投影。
     *
     * 「左上区有颗牙是残根」说的是一个区，不是某颗牙。dental_charts 一行就是
     * 一颗牙的当前状态，写不出「某个区的某颗牙」—— 硬写会得到 tooth_number='1'，
     * 而 1 在 FDI 里不是牙位，牙位图上会多出一颗根本不存在的牙。
     */
    public function test_象限码不投影到牙位图(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'teeth' => [['no' => '1', 'mark' => 'residual_root']], 'content' => '右上区残根'],
        ]);

        $this->assertSame(
            0,
            DentalChart::where('medical_case_id', $case->id)->count(),
            '象限码没有具体牙位，不该在牙位图上落一条'
        );
    }

    /** 同一行里具体牙位照常投影，象限码被跳过 —— 两者互不影响。 */
    public function test_复合行里只投影具体牙位(): void
    {
        $case = $this->makeCase();

        $this->save($case, [
            ['section' => 'examination', 'teeth' => [
                ['no' => '45', 'mark' => 'residual_root'],
                ['no' => '1',  'mark' => 'extracted'],
            ], 'content' => '检查所见'],
        ]);

        $this->assertSame(1, DentalChart::where('medical_case_id', $case->id)->count());
        $this->assertDatabaseHas('dental_charts', [
            'medical_case_id' => $case->id, 'tooth_number' => '45', 'tooth_status' => 'residual_root',
        ]);
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
