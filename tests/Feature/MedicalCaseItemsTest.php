<?php

namespace Tests\Feature;

use App\Branch;
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
 * 病历分段明细：每一行是「牙位 + 文字」。
 *
 * 原来每段一个 textarea + 整段共享一个牙位列表（examination_teeth = ["45","36"]），
 * 「45 缺失」和「36 龋坏」揉在一段话里，文字和牙位对不上号 —— 于是「45 这颗牙历次
 * 做过什么」查不出来。同类产品的病历里，检查/其他检查/诊断/治疗每段都是可重复的
 * 「牙位 + 文字」行。
 *
 * 这组用例钉住的核心是**派生方向**：行是唯一可编辑来源，medical_cases 上的文本列
 * 与牙位列都由行渲染出来。文本列保留是因为打印、病历详情、API、OCR、工作日志
 * 五处都在消费它。派生若反向或双向，同一段临床文字就有了两个来源。
 */
class MedicalCaseItemsTest extends TestCase
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
            'patient_no' => '20260930', 'surname' => '陈', 'othername' => '十三',
            'gender' => 'Male', 'phone_no' => '13800138013', '_who_added' => $this->doctor->id,
        ]);
    }

    /** 牙位标记那几条用例的病历夹具 */
    private function makeCase(): MedicalCase
    {
        return MedicalCase::create([
            'case_no'         => MedicalCase::CaseNumber(),
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->doctor->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => '牙疼',
            'status'          => MedicalCase::STATUS_OPEN,
            '_who_added'      => $this->doctor->id,
        ]);
    }

    private function service(): MedicalCaseService
    {
        return app(MedicalCaseService::class);
    }

    /**
     * 注意：诊断**不在** case_items 里 —— 它走 diagnoses 表（带 ICD 编码），
     * 见 MedicalCaseItem::SECTIONS 的注释与 DiagnosisRowsTest。
     */
    private function rows(): array
    {
        return [
            ['section' => 'examination', 'tooth_no' => '45', 'content' => '缺失，未修复，牙槽嵴丰满'],
            ['section' => 'examination', 'tooth_no' => '36', 'content' => '龋坏'],
            ['section' => 'treatment',   'tooth_no' => '45', 'content' => '制取上下颌藻酸盐印模'],
        ];
    }

    /**
     * @param bool $asDraft 非草稿会被 lock()，之后的更新要走修订审批路径。
     *   测「保存后再改」时必须用草稿，否则测到的是合规拦截而不是行同步。
     */
    private function createCaseWithRows(array $rows = null, bool $asDraft = false): MedicalCase
    {
        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => '左下后牙缺失要求镶牙',
            'case_items'      => $rows ?? $this->rows(),
        ]);

        return $this->service()->createCase($data, $asDraft);
    }

    /** @test */
    public function 分行录入会落成明细行(): void
    {
        $case = $this->createCaseWithRows();

        $this->assertSame(3, MedicalCaseItem::where('medical_case_id', $case->id)->count());

        $exam = MedicalCaseItem::where('medical_case_id', $case->id)
            ->section('examination')->orderBy('sort_order')->get();

        $this->assertSame(['45', '36'], $exam->pluck('tooth_no')->all());
        $this->assertSame('缺失，未修复，牙槽嵴丰满', $exam->first()->content);
    }

    /**
     * 这条就是整张表存在的理由：按牙位查历次做过什么。
     * 旧结构做不到 —— 牙位只是整段共享的一个标签集合。
     */
    /** @test */
    public function 能按牙位查出历次记录(): void
    {
        $this->createCaseWithRows();

        $forTooth45 = MedicalCaseItem::forTooth('45')->get();

        $this->assertCount(2, $forTooth45, '45 在检查/治疗两段各有一条（诊断走 diagnoses 表）');
        $this->assertSame(
            ['examination', 'treatment'],
            $forTooth45->pluck('section')->sort()->values()->all()
        );

        $this->assertCount(1, MedicalCaseItem::forTooth('36')->get());
        $this->assertCount(0, MedicalCaseItem::forTooth('11')->get());
    }

    /** @test */
    public function 文本列由行派生成牙位加内容的逐行文字(): void
    {
        $case = $this->createCaseWithRows()->fresh();

        $this->assertSame("45 缺失，未修复，牙槽嵴丰满\n36 龋坏", $case->examination);
        $this->assertSame('45 制取上下颌藻酸盐印模', $case->treatment);
    }

    /**
     * 牙位列也从行派生：侧栏牙位图、打印、API 都在读它，不同步就会和明细打架。
     */
    /** @test */
    public function 牙位列由行的牙位去重派生(): void
    {
        $case = $this->createCaseWithRows()->fresh();

        $this->assertSame(['45', '36'], $case->examination_teeth);
    }

    /**
     * 前端直接提交的 examination / examination_teeth 不能盖掉行派生的值，
     * 否则一份病历里两个来源，改一处漏一处。
     */
    /** @test */
    public function 提交行时文本列以行为准(): void
    {
        $data = $this->service()->buildCaseData([
            'patient_id'        => $this->patient->id,
            'case_date'         => now()->format('Y-m-d'),
            'chief_complaint'   => '主诉',
            'examination'       => '这是前端直接传的整段文字，应当被行覆盖',
            'examination_teeth' => json_encode(['11', '12']),
            'case_items'        => [
                ['section' => 'examination', 'tooth_no' => '45', 'content' => '缺失'],
            ],
        ]);
        $case = $this->service()->createCase($data, false)->fresh();

        $this->assertSame('45 缺失', $case->examination);
        $this->assertSame(['45'], $case->examination_teeth);
    }

    /** @test */
    public function 没有牙位的整体描述也能存(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination', 'tooth_no' => '', 'content' => '口腔卫生一般，牙石 I 度'],
        ])->fresh();

        $row = MedicalCaseItem::where('medical_case_id', $case->id)->first();

        $this->assertNull($row->tooth_no);
        $this->assertSame('口腔卫生一般，牙石 I 度', $case->examination, '无牙位时只出内容');
        $this->assertNull($case->examination_teeth);
    }

    /**
     * 用户点了「添加」又没填的空行不该攒在库里。
     */
    /**
     * 一行可以写多颗牙（「16、17 缺失」是一条，不用写两遍）。
     *
     * 但落库仍然一牙一行 —— forTooth('16') 得查得到，那是这张表存在的理由；
     * 存成 '16,17' 就得靠 LIKE 去猜，索引也废了。合并只是写法与显示。
     */
    /** @test */
    public function 一行多颗牙落库时按牙拆开(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination', 'tooth_no' => '16,17', 'content' => '缺失'],
        ]);

        $rows = MedicalCaseItem::where('medical_case_id', $case->id)->get();

        $this->assertCount(2, $rows, '两颗牙 = 两行');
        $this->assertSame(['16', '17'], $rows->pluck('tooth_no')->all());
        $this->assertSame(['缺失', '缺失'], $rows->pluck('content')->all());

        // 按牙位查得到 —— 这条是重点
        $this->assertCount(1, MedicalCaseItem::forTooth('16')->get());
        $this->assertCount(1, MedicalCaseItem::forTooth('17')->get());
    }

    /**
     * 读回编辑器时按「同段落 + 同内容」合并回一行，医生看到的还是他写的那一条。
     */
    /** @test */
    public function 读回编辑器时同内容的牙位合并回一行(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination', 'tooth_no' => '16,17', 'content' => '缺失'],
            ['section' => 'examination', 'tooth_no' => '36',    'content' => '龋坏'],
        ]);

        $items = $this->service()->getCaseItemsForEdit($case);

        $this->assertCount(2, $items['examination'], '两条内容 = 两行');
        $this->assertSame('16,17', $items['examination'][0]['tooth_no']);
        $this->assertSame('缺失', $items['examination'][0]['content']);
        $this->assertSame('36', $items['examination'][1]['tooth_no']);
    }

    /** @test */
    public function 多颗牙时牙位列包含每一颗(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination', 'tooth_no' => '16, 17', 'content' => '缺失'],
        ])->fresh();

        $this->assertSame(['16', '17'], $case->examination_teeth);
        $this->assertSame("16 缺失\n17 缺失", $case->examination);
    }

    /** @test */
    public function 空行被丢弃(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination', 'tooth_no' => '45', 'content' => '缺失'],
            ['section' => 'examination', 'tooth_no' => '',   'content' => ''],
            ['section' => 'examination', 'tooth_no' => '  ', 'content' => '   '],
        ]);

        $this->assertSame(1, MedicalCaseItem::where('medical_case_id', $case->id)->count());
    }

    /** @test */
    public function 不认识的段落名被忽略(): void
    {
        $case = $this->createCaseWithRows([
            ['section' => 'examination',     'tooth_no' => '45', 'content' => '缺失'],
            ['section' => 'diagnosis',       'tooth_no' => '45', 'content' => '诊断走 diagnoses 表'],
            ['section' => 'chief_complaint', 'tooth_no' => '45', 'content' => '主诉不分行'],
            ['section' => 'nonsense',        'tooth_no' => '45', 'content' => '瞎写的段落'],
        ]);

        $this->assertSame(1, MedicalCaseItem::where('medical_case_id', $case->id)->count());
    }

    /** @test */
    public function 再次保存是整段替换而不是追加(): void
    {
        $case = $this->createCaseWithRows(asDraft: true);
        $this->assertSame(3, MedicalCaseItem::where('medical_case_id', $case->id)->count());

        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => '左下后牙缺失要求镶牙',
            'case_items'      => [
                ['section' => 'examination', 'tooth_no' => '11', 'content' => '改过了'],
            ],
        ], isUpdate: true);
        $this->service()->updateCase($case->id, $data, true);

        $live = MedicalCaseItem::where('medical_case_id', $case->id)->get();
        $this->assertCount(1, $live);
        $this->assertSame('11', $live->first()->tooth_no);
        $this->assertSame('11 改过了', $case->fresh()->examination);
    }

    /**
     * 软删而非硬删：病历有审计要求，被替换掉的行要留痕。
     */
    /** @test */
    public function 被替换的行是软删(): void
    {
        $case = $this->createCaseWithRows(asDraft: true);

        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => 'x',
            'case_items'      => [['section' => 'examination', 'tooth_no' => '11', 'content' => 'y']],
        ], isUpdate: true);
        $this->service()->updateCase($case->id, $data, true);

        $this->assertSame(
            3,
            MedicalCaseItem::onlyTrashed()->where('medical_case_id', $case->id)->count(),
            '原来的 3 行应当留在库里（软删）'
        );
    }

    /**
     * 锁定病历（已提交）改行要走修订审批，行不能当场落库。
     *
     * 审批通过后生效的是文本列 —— createAmendment 只 diff getFillable() 里的键，
     * 行不在其中。所以行必须在走修订前就摘掉，否则未经审批的行会直接进库，
     * 而文本列还是旧的，两边打架。
     */
    /** @test */
    public function 锁定病历改行走修订且行不落库(): void
    {
        $case = $this->createCaseWithRows();   // 非草稿 → 已锁定
        $this->assertTrue($case->fresh()->is_locked);

        $data = $this->service()->buildCaseData([
            'patient_id'      => $this->patient->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => 'x',
            'case_items'      => [['section' => 'examination', 'tooth_no' => '11', 'content' => '偷改']],
        ], isUpdate: true);

        // 不给修订理由：直接拒绝
        $refused = $this->service()->updateCase($case->id, $data, false);
        $this->assertFalse($refused['status']);
        $this->assertTrue($refused['require_reason']);

        // 给了理由：转成修订申请，行仍然不落库
        $result = $this->service()->updateCase($case->id, $data, false, '录入笔误');
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('amendment_id', $result);

        $live = MedicalCaseItem::where('medical_case_id', $case->id)->get();
        $this->assertCount(3, $live, '原来的 3 行应当原样留着');
        $this->assertNotContains('11', $live->pluck('tooth_no')->all(), '未审批的行不该进库');
    }

    /**
     * 老病历没有行：按整段文字合成一行返回，tooth_no 为 null。
     * 一段话没法自动拆成按牙位的行，硬拆只会把病历弄乱。
     */
    /** @test */
    public function 老病历按整段合成一行给编辑器(): void
    {
        $legacy = MedicalCase::create([
            'case_no'         => MedicalCase::CaseNumber(),
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->doctor->id,
            'case_date'       => now()->format('Y-m-d'),
            'chief_complaint' => '牙疼',
            'examination'     => '45 缺失，36 龋坏',   // 老结构：一段话
            'diagnosis'       => '牙列缺损',
            'status'          => MedicalCase::STATUS_OPEN,
            '_who_added'      => $this->doctor->id,
        ]);

        $items = $this->service()->getCaseItemsForEdit($legacy);

        $this->assertCount(1, $items['examination']);
        $this->assertNull($items['examination'][0]['tooth_no']);
        $this->assertSame('45 缺失，36 龋坏', $items['examination'][0]['content']);
        $this->assertSame([], $items['treatment'], '空段落不合成行');
    }

    /** @test */
    public function 有行的病历直接返回行(): void
    {
        $case = $this->createCaseWithRows();

        $items = $this->service()->getCaseItemsForEdit($case);

        $this->assertCount(2, $items['examination']);
        $this->assertSame('45', $items['examination'][0]['tooth_no']);
        $this->assertCount(1, $items['treatment']);
        $this->assertArrayNotHasKey('diagnosis', $items, '诊断不在 case_items 里');
    }

    /** @test */
    public function 新建时没有病历返回空段落(): void
    {
        $items = $this->service()->getCaseItemsForEdit(null);

        foreach (MedicalCaseItem::SECTIONS as $section) {
            $this->assertSame([], $items[$section]);
        }
    }

    // ─── 牙位标记 △ 残根 / ✕ 已拔除 / — 缺失 ────────────────────

    /**
     * 标记落在**行**上：一行是一条临床陈述，「16,17 残根」就是两颗都残根。
     * 落库仍是一牙一行，每行各带一份标记。
     */
    public function test_牙位标记随行落库(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '16,17',
             'tooth_mark' => 'residual_root', 'content' => '冠部大面积缺损'],
        ])['items']);

        $this->assertSame(2, MedicalCaseItem::where('medical_case_id', $case->id)->count());
        foreach (['16', '17'] as $tooth) {
            $this->assertDatabaseHas('medical_case_items', [
                'medical_case_id' => $case->id,
                'tooth_no'        => $tooth,
                'tooth_mark'      => 'residual_root',
            ]);
        }
    }

    /**
     * 认不出的标记当没填 —— 宁可少一个标记，也不要把前端传来的任意字符串塞进库里。
     */
    public function test_不认识的标记被丢掉(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '16',
             'tooth_mark' => '<script>', 'content' => '龋坏'],
        ])['items']);

        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id, 'tooth_no' => '16', 'tooth_mark' => null,
        ]);
    }

    /**
     * 符号要跟着牙位号进派生文本。不带的话打印出来只有「45 …」，
     * 看不出这颗牙是残根还是已拔除 —— 而文本列正是打印、详情、API、OCR、
     * 工作日志五处在消费的东西。
     */
    public function test_标记符号进派生文本(): void
    {
        $out = $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '45',
             'tooth_mark' => 'residual_root', 'content' => '仅存牙根'],
            ['section' => 'treatment', 'tooth_no' => '38',
             'tooth_mark' => 'extracted', 'content' => '已拔除'],
        ]);

        $this->assertSame('45△ 仅存牙根', $out['columns']['examination']);
        $this->assertSame('38✕ 已拔除', $out['columns']['treatment']);
    }

    /**
     * 只写符号、不写牙位号 —— 部位记录法里「符号直接取代数字」的写法。
     *
     * 医生在格子里直接画一个 △，意思是「这个区有颗牙是残根」，连牙位号都不写。
     * 这一行的 content 和 tooth_no 都是空的，但它**不是**空行：带着一个标记的行
     * 是一条临床陈述。之前被当成「点了添加又没填」整行丢掉了。
     */
    public function test_只有标记的行不算空行(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '', 'tooth_mark' => 'residual_root', 'content' => ''],
        ])['items']);

        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id,
            'tooth_no'        => null,
            'tooth_mark'      => 'residual_root',
            'content'         => null,
        ]);
    }

    /**
     * 不写牙位号时，符号顶替数字的位置进派生文本。
     *
     * 原来符号只在 tooth_no 非空时才拼进去，于是「不填牙位、只标 △ 加一句话」
     * 打印出来只剩那句话，符号无声消失 —— 而文本列是打印、详情、API、OCR、
     * 工作日志五处在消费的东西。
     */
    public function test_没有牙位时符号顶替数字进派生文本(): void
    {
        $out = $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '', 'tooth_mark' => 'residual_root', 'content' => '仅存牙根'],
            ['section' => 'treatment',   'tooth_no' => '', 'tooth_mark' => 'extracted',     'content' => ''],
        ]);

        $this->assertSame('△ 仅存牙根', $out['columns']['examination']);
        $this->assertSame('✕', $out['columns']['treatment']);
    }

    /**
     * 复合行读回编辑器时仍是**一行**，每个牙位带着自己的标记。
     *
     * 按「同段落 + 同内容 + 同标记」合并的话，45（无标记）和象限码 1（带 △）
     * 会被拆成两行 —— 医生画的那一个十字，刷新一次就散了。
     */
    public function test_复合行读回编辑器仍是一行(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [
                ['no' => '45', 'mark' => null],
                ['no' => '1',  'mark' => 'residual_root'],
            ], 'content' => '检查所见'],
        ])['items']);

        $rows = $this->service()->getCaseItemsForEdit($case->fresh())['examination'];

        $this->assertCount(1, $rows, '一个十字读回来应当仍是一行');
        $this->assertSame('45,1', $rows[0]['tooth_no']);
        $this->assertSame(
            [['no' => '45', 'mark' => null], ['no' => '1', 'mark' => 'residual_root']],
            $rows[0]['teeth']
        );
    }

    // ─── 部位记录法的三种体现方式（照医生给的样例验收）─────────────

    /**
     * 方式 A：符号直接取代数字。
     *
     * 医生在左上格里只画一个 △，不写牙位号 —— 意思是「患者右上区有颗牙是残根」。
     * 两位 FDI 全码表达不了（它必须指到具体某颗牙），用一位数字表示象限。
     */
    public function test_方式A_象限码带标记能落库(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [['no' => '1', 'mark' => 'residual_root']], 'content' => ''],
        ])['items']);

        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id,
            'tooth_no'        => '1',
            'tooth_mark'      => 'residual_root',
        ]);
    }

    /** 方式 A：象限码的派生文本是符号本身，不是「1△」—— 数字并没有被写出来。 */
    public function test_方式A_象限码派生文本只有符号(): void
    {
        $out = $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [['no' => '1', 'mark' => 'residual_root']], 'content' => '仅存牙根'],
        ]);

        $this->assertSame('△ 仅存牙根', $out['columns']['examination']);
    }

    /** 方式 A：象限码不是牙位，不该混进牙位列。 */
    public function test_方式A_象限码不进牙位列(): void
    {
        $out = $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [
                ['no' => '45', 'mark' => null],
                ['no' => '1',  'mark' => 'residual_root'],
            ], 'content' => '检查所见'],
        ]);

        $this->assertSame(['45'], $out['columns']['examination_teeth']);
    }

    /**
     * 复合：一个十字里，左下写牙位号、左上单独一个 △。
     *
     * 这是医生最常写的形式，也是标记从「每行一个」改成「每条目一个」的理由 ——
     * 行上只有一个标记的话，渲染时会把 △ 盖到每一个有牙的格子上。
     */
    public function test_复合_同一行里牙位与象限符号各自独立(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [
                ['no' => '45', 'mark' => null],
                ['no' => '1',  'mark' => 'residual_root'],
            ], 'content' => '检查所见'],
        ])['items']);

        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id, 'tooth_no' => '45', 'tooth_mark' => null,
        ]);
        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id, 'tooth_no' => '1', 'tooth_mark' => 'residual_root',
        ]);
    }

    /** 复合：同一行里两颗牙带不同的标记。 */
    public function test_复合_两颗牙各带不同标记(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [
                ['no' => '16', 'mark' => 'residual_root'],
                ['no' => '17', 'mark' => 'extracted'],
            ], 'content' => '检查所见'],
        ])['items']);

        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id, 'tooth_no' => '16', 'tooth_mark' => 'residual_root',
        ]);
        $this->assertDatabaseHas('medical_case_items', [
            'medical_case_id' => $case->id, 'tooth_no' => '17', 'tooth_mark' => 'extracted',
        ]);
    }

    /** 旧载荷（tooth_no 逗号串 + 行级 tooth_mark）仍然可用。 */
    public function test_旧载荷格式仍然兼容(): void
    {
        $out = $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'tooth_no' => '16,17', 'tooth_mark' => 'residual_root', 'content' => '残根'],
        ]);

        $this->assertSame("16△ 残根\n17△ 残根", $out['columns']['examination']);
    }

    /**
     * 同段落同内容、标记不同的两颗牙，读回来是**一行两个条目**，各带各的标记。
     *
     * 这条用例原来断言的是相反的行为（拆成两行）—— 那是标记还挂在「行」上时的
     * 唯一出路：一行只能有一个标记，16 残根和 17 已拔除就只能分开放。标记改成
     * 每个牙位各自一个之后，它们本来就是同一个十字里的两颗牙，合成一行才对，
     * 也才画得出医生写的那个十字。
     */
    public function test_同内容不同标记的牙合成一行各带各的标记(): void
    {
        $case = $this->makeCase();

        $this->service()->syncCaseItems($case, $this->service()->normalizeCaseItems([
            ['section' => 'examination', 'teeth' => [
                ['no' => '16', 'mark' => 'residual_root'],
                ['no' => '17', 'mark' => 'extracted'],
            ], 'content' => '缺损'],
        ])['items']);

        $rows = $this->service()->getCaseItemsForEdit($case->fresh())['examination'];

        $this->assertCount(1, $rows, '同一个十字读回来应当仍是一行');
        $this->assertSame('16,17', $rows[0]['tooth_no']);
        $this->assertSame(
            [['no' => '16', 'mark' => 'residual_root'], ['no' => '17', 'mark' => 'extracted']],
            $rows[0]['teeth']
        );
        $this->assertNull($rows[0]['tooth_mark'], '各牙标记不同时行级标记无从表达，应为 null');
    }
}
