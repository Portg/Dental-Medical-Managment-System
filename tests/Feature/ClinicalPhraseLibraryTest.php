<?php

namespace Tests\Feature;

use App\Branch;
use App\QuickPhrase;
use App\Role;
use App\User;
use Database\Seeders\ClinicalPhraseLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 临床短语库：按语义槽位分组、短语自带标点、{} 是光标位。
 *
 * 原来短语只按 category 平铺成三个大类（检查 / 诊断 / 治疗），能查到词但不教人
 * 怎么把一句话说完整。参考产品是按句子的语义槽位分组的 —— 现病史那一栏顺着
 * 「时间 → 部位 → 症状 → 治疗情况 → 症状变化」点下来，一句话自然成形。
 *
 * 这组用例钉的是库的**形状**，不是具体条目（条目医生一定会改）：槽位在不在、
 * 顺序对不对、标点在不在短语里、光标位标记用的是 {} 而不是 __。
 */
class ClinicalPhraseLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);

        $this->seed(ClinicalPhraseLibrarySeeder::class);
    }

    public function test_九个病历字段都有短语(): void
    {
        $fields = [
            'chief_complaint', 'present_illness', 'past_history',
            'examination', 'auxiliary_examination', 'diagnosis',
            'treatment_plan', 'treatment', 'medical_orders',
        ];

        foreach ($fields as $field) {
            $slots = QuickPhrase::slotsForField($field, $this->doctor->id);

            $this->assertNotEmpty($slots, "{$field} 一条短语都没有");
            $this->assertFalse($slots->has(''), "{$field} 有短语没归到槽位里");
        }
    }

    /**
     * 现病史的槽位顺序就是一句现病史的句子结构，乱了就不成句。
     */
    public function test_现病史的槽位按句子结构排序(): void
    {
        $slots = QuickPhrase::slotsForField('present_illness', $this->doctor->id);

        $this->assertSame(
            ['时间', '部位', '症状', '治疗情况', '症状变化', '口腔习惯'],
            $slots->keys()->all()
        );
    }

    /**
     * 「时间」这一槽里 1天前 必须排在 1周前 前面 —— 靠 id 排不住，
     * 所以 sort_order 是必需的。
     */
    public function test_槽位内按时间先后排而不是按插入顺序(): void
    {
        $times = QuickPhrase::slotsForField('present_illness', $this->doctor->id)
            ->get('时间')->pluck('phrase')->all();

        $this->assertSame('1小时前', $times[0]);
        $this->assertLessThan(
            array_search('1周前', $times, true),
            array_search('1天前', $times, true),
            '1天前 应当排在 1周前 之前'
        );
    }

    /**
     * 标点在短语里。点两条不该粘成「本院治疗牙髓治疗」。
     */
    public function test_句中短语自带标点(): void
    {
        $treated = QuickPhrase::where('category', 'present_illness')
            ->where('slot', '治疗情况')->pluck('phrase');

        $this->assertContains('本院治疗，', $treated);
        $this->assertContains('未治疗。', $treated);

        // 连点两条应当直接成句
        $composed = '本院治疗，' . '缓解，';
        $this->assertSame('本院治疗，缓解，', $composed);
    }

    /**
     * 医嘱多为句末项，收尾该是句号不是逗号。
     */
    public function test_医嘱以句号收尾(): void
    {
        $orders = QuickPhrase::where('category', 'medical_orders')->pluck('phrase');

        foreach ($orders as $phrase) {
            $this->assertStringEndsWith('。', $phrase, "医嘱「{$phrase}」应当以句号收尾");
        }
    }

    /**
     * 光标位用 {}，不能用 __ —— 后者在病历模板里已经表示「替换成本行牙位」，
     * 两个含义撞车的话，插一条「PD=__mm」会被替换成牙位号。
     */
    public function test_光标位用花括号不与牙位占位符撞车(): void
    {
        $withCaret = QuickPhrase::where('phrase', 'like', '%' . QuickPhrase::CARET . '%')->get();

        $this->assertNotEmpty($withCaret, '应当有带光标位的半成品短语');

        foreach ($withCaret as $p) {
            $this->assertStringNotContainsString('__', $p->phrase, "「{$p->phrase}」不该用 __ 当占位符");
            $this->assertTrue($p->has_caret);
        }

        // 牙周检查那几条是典型
        $perio = QuickPhrase::where('category', 'examination')->where('slot', '牙周')->pluck('phrase');
        $this->assertContains('PD={}mm，', $perio);
    }

    /**
     * 重复执行不翻倍 —— 升级脚本会反复跑 seeder。
     */
    public function test_重复执行不产生重复短语(): void
    {
        $before = QuickPhrase::count();

        $this->seed(ClinicalPhraseLibrarySeeder::class);

        $this->assertSame($before, QuickPhrase::count());
    }

    /**
     * 新库 migrate --seed 必须带上临床短语库，否则只有空分类、面板没得点。
     */
    public function test_DatabaseSeeder_挂了临床短语库(): void
    {
        $src = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertNotFalse($src);
        $this->assertStringContainsString(
            'ClinicalPhraseLibrarySeeder',
            $src,
            '新装机若不挂这个 seeder，病历锚定面板九段都是空的'
        );
        $catPos = strpos($src, 'QuickPhraseCategoriesSeeder');
        $libPos = strpos($src, 'ClinicalPhraseLibrarySeeder');
        $this->assertNotFalse($catPos);
        $this->assertNotFalse($libPos);
        $this->assertGreaterThan(
            $catPos,
            $libPos,
            '必须先有分类再灌短语内容'
        );
    }

    /**
     * 锚定面板要的整份数据：[字段 => [槽位 => [短语]]]，一次性下发不做按字段 ajax。
     */
    public function test_面板数据九个字段都在且带槽位(): void
    {
        $panel = QuickPhrase::panelForUser($this->doctor->id);

        $this->assertCount(9, $panel);
        $this->assertSame(
            ['时间', '部位', '症状', '治疗情况', '症状变化', '口腔习惯'],
            array_keys($panel['present_illness'])
        );
        $this->assertContains('本院治疗，', $panel['present_illness']['治疗情况']);
    }

    /**
     * 诊所自己早先加的短语没有槽位，归到「其他」并排最后 ——
     * 它们 sort_order 是 0，不挪就会顶在「时间」「龋坏」这些正经槽位前面，
     * 而面板第一眼该看到的是句子的起手。
     */
    public function test_没归槽位的老短语排到最后(): void
    {
        QuickPhrase::create([
            'shortcut' => '', 'phrase' => '诊所自己加的', 'category' => 'examination',
            'slot' => null, 'sort_order' => 0, 'scope' => 'system',
            'is_active' => true, '_who_added' => $this->doctor->id,
        ]);

        $slots = array_keys(QuickPhrase::panelForUser($this->doctor->id)['examination']);

        $this->assertSame(__('common.other'), end($slots), '「其他」应当排在最后');
        $this->assertSame('牙髓测试', $slots[0], '第一组应当是句子的起手');
    }


    /**
     * 治疗计划与治疗是两段，短语库也得分开 ——
     * 「活动义齿修复」是计划，「制取硅橡胶印模，」是本次处置。
     */
    public function test_治疗计划与治疗的短语分属两段(): void
    {
        $plan = QuickPhrase::where('category', 'treatment_plan')->pluck('phrase');
        $done = QuickPhrase::where('category', 'treatment')->pluck('phrase');

        $this->assertContains('活动义齿修复', $plan);
        $this->assertNotContains('活动义齿修复', $done);

        $this->assertContains('制取硅橡胶印模，', $done);
        $this->assertNotContains('制取硅橡胶印模，', $plan);
    }
}
