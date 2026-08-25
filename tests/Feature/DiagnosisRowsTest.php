<?php

namespace Tests\Feature;

use App\Branch;
use App\Diagnosis;
use App\MedicalCase;
use App\MedicalCaseItem;
use App\Patient;
use App\Role;
use App\Services\MedicalCaseService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 病历的「诊断」段落在 diagnoses 表，不在 medical_case_items。
 *
 * 背景：这个项目里诊断本来有两个去处，互不相通 ——
 *   medical_cases.diagnosis   主流程（新建病历那一屏）写这里，一段文本
 *   diagnoses 表              只能从病历详情页的「诊断记录」Tab 单独添加
 *
 * diagnoses 一直是 0 条，不是功能不好，是**主流程走不到**：医生的动作是
 * 「新建病历 → 一屏填完 → 提交」，不会填完再拐到详情页点一次「添加诊断」。
 *
 * 现在诊断段直接写 diagnoses。选它而不是 medical_case_items，是因为它带
 * ICD 编码、严重程度、转归状态 —— ICD 是医保与病案质控要的，另一张表存不了。
 * 同一条诊断不在两张表里各存一份。
 *
 * 这组用例钉五件事：
 *   1. 诊断落 diagnoses 而不是 medical_case_items
 *   2. ICD 编码存得下、按牙位查得到
 *   3. 一行多颗牙落库时按牙拆开（与 case_items 同样的规则）
 *   4. medical_cases.diagnosis 与 related_teeth 仍由诊断行派生（打印/API 在读）
 *   5. 老病历没有 diagnoses 记录时按整段文字兜底，不做破坏性迁移
 */
class DiagnosisRowsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);
        Auth::login($this->doctor);

        $this->patient = Patient::create([
            'patient_no' => '20261101', 'surname' => '褚', 'othername' => '十六',
            'gender' => 'Male', 'phone_no' => '13800138016', '_who_added' => $this->doctor->id,
        ]);
    }

    private function service(): MedicalCaseService
    {
        return app(MedicalCaseService::class);
    }

    private function createCase(array $diagnosisRows, bool $asDraft = true): MedicalCase
    {
        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => '右上后牙冷热敏感一周',
            'case_items'      => [
                ['section' => 'examination', 'tooth_no' => '16', 'content' => '龋坏'],
            ],
            'diagnosis_rows'  => $diagnosisRows,
        ]);

        return $this->service()->createCase($data, $asDraft);
    }

    /** @test */
    public function 诊断落在diagnoses表而不是明细表(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16', 'content' => '中龋', 'icd_code' => 'K02.1'],
        ]);

        $this->assertSame(1, Diagnosis::where('medical_case_id', $case->id)->count());
        $this->assertSame(
            0,
            MedicalCaseItem::where('medical_case_id', $case->id)->section('diagnosis')->count(),
            '诊断不该同时出现在 medical_case_items 里'
        );
    }

    /**
     * ICD 编码是选 diagnoses 而不是 medical_case_items 的理由 —— 医保与
     * 病案质控要的就是这个，另一张表存不了。
     */
    /** @test */
    public function ICD编码与严重程度存得下(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16', 'content' => '牙髓炎', 'icd_code' => 'K04.0', 'severity' => 'Moderate'],
        ]);

        $dx = Diagnosis::where('medical_case_id', $case->id)->first();

        $this->assertSame('牙髓炎', $dx->diagnosis_name);
        $this->assertSame('K04.0', $dx->icd_code);
        $this->assertSame('Moderate', $dx->severity);
        $this->assertSame('16', $dx->tooth_no);
    }

    /** @test */
    public function 能按牙位查出历次诊断(): void
    {
        $this->createCase([
            ['tooth_no' => '16', 'content' => '中龋', 'icd_code' => 'K02.1'],
            ['tooth_no' => '36', 'content' => '慢性根尖周炎', 'icd_code' => 'K04.5'],
        ]);

        $this->assertCount(1, Diagnosis::forTooth('16')->get());
        $this->assertCount(1, Diagnosis::forTooth('36')->get());
        $this->assertCount(0, Diagnosis::forTooth('11')->get());
    }

    /**
     * 与 medical_case_items 同样的规则：写法上一行可以多颗牙，落库一牙一条 ——
     * forTooth('16') 得查得到。
     */
    /** @test */
    public function 一行多颗牙落库时按牙拆开(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16,17', 'content' => '中龋', 'icd_code' => 'K02.1'],
        ]);

        $rows = Diagnosis::where('medical_case_id', $case->id)->orderBy('sort_order')->get();

        $this->assertCount(2, $rows);
        $this->assertSame(['16', '17'], $rows->pluck('tooth_no')->all());
        $this->assertSame(['K02.1', 'K02.1'], $rows->pluck('icd_code')->all());
    }

    /**
     * 打印、病历详情、API、OCR、工作日志都在读 medical_cases.diagnosis，
     * 不该为这次改动而改 —— 文本列仍由诊断行派生。
     */
    /** @test */
    public function 文本列与牙位列仍由诊断行派生(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16', 'content' => '中龋', 'icd_code' => 'K02.1'],
            ['tooth_no' => '36', 'content' => '慢性根尖周炎'],
        ])->fresh();

        $this->assertSame("16 中龋（K02.1）\n36 慢性根尖周炎", $case->diagnosis);
        $this->assertSame(['16', '36'], $case->related_teeth);
    }

    /** @test */
    public function 没有牙位的整体诊断也能存(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '', 'content' => '慢性牙周炎', 'icd_code' => 'K05.3'],
        ])->fresh();

        $dx = Diagnosis::where('medical_case_id', $case->id)->first();

        $this->assertNull($dx->tooth_no);
        $this->assertSame('慢性牙周炎（K05.3）', $case->diagnosis);
        $this->assertNull($case->related_teeth);
    }

    /** @test */
    public function 只选牙位没写诊断名的行被丢弃(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16', 'content' => '中龋'],
            ['tooth_no' => '17', 'content' => ''],
        ]);

        $this->assertSame(1, Diagnosis::where('medical_case_id', $case->id)->count());
    }

    /** @test */
    public function 再次保存是整段替换且旧记录软删(): void
    {
        $case = $this->createCase([['tooth_no' => '16', 'content' => '中龋']]);

        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => 'x',
            'diagnosis_rows'  => [['tooth_no' => '26', 'content' => '深龋']],
        ], isUpdate: true);
        $this->service()->updateCase($case->id, $data, true);

        $live = Diagnosis::where('medical_case_id', $case->id)->get();
        $this->assertCount(1, $live);
        $this->assertSame('26', $live->first()->tooth_no);
        $this->assertSame(1, Diagnosis::onlyTrashed()->where('medical_case_id', $case->id)->count());
    }

    /**
     * 锁定病历改诊断要走修订审批，诊断不能当场落库 —— 与 case_items 同一条纪律。
     */
    /** @test */
    public function 锁定病历改诊断走修订且不落库(): void
    {
        $case = $this->createCase([['tooth_no' => '16', 'content' => '中龋']], asDraft: false);
        $this->assertTrue($case->fresh()->is_locked);

        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => 'x',
            'diagnosis_rows'  => [['tooth_no' => '26', 'content' => '偷改']],
        ], isUpdate: true);

        $result = $this->service()->updateCase($case->id, $data, false, '录入笔误');
        $this->assertArrayHasKey('amendment_id', $result);

        $live = Diagnosis::where('medical_case_id', $case->id)->get();
        $this->assertCount(1, $live);
        $this->assertSame('16', $live->first()->tooth_no, '未审批的诊断不该进库');
    }

    /**
     * 老病历没有 diagnoses 记录：按 medical_cases.diagnosis 整段合成一行。
     * 一段话没法自动拆成按牙位的诊断，硬拆只会把病历弄乱。
     */
    /** @test */
    public function 老病历按整段文字兜底(): void
    {
        $legacy = MedicalCase::create([
            'case_no'    => MedicalCase::CaseNumber(),
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'case_date'  => now()->format('Y-m-d'),
            'chief_complaint' => '牙疼',
            'diagnosis'  => '16 中龋，36 慢性根尖周炎',
            'status'     => MedicalCase::STATUS_OPEN,
            '_who_added' => $this->doctor->id,
        ]);

        $rows = $this->service()->getDiagnosesForEdit($legacy);

        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['tooth_no']);
        $this->assertSame('16 中龋，36 慢性根尖周炎', $rows[0]['content']);
    }

    /** @test */
    public function 读回编辑器时同诊断的多颗牙合并回一行(): void
    {
        $case = $this->createCase([
            ['tooth_no' => '16,17', 'content' => '中龋', 'icd_code' => 'K02.1'],
            ['tooth_no' => '36',    'content' => '慢性根尖周炎'],
        ]);

        $rows = $this->service()->getDiagnosesForEdit($case);

        $this->assertCount(2, $rows);
        $this->assertSame('16,17', $rows[0]['tooth_no']);
        $this->assertSame('K02.1', $rows[0]['icd_code']);
        $this->assertSame('36', $rows[1]['tooth_no']);
    }
}
