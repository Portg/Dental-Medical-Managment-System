<?php

namespace Tests\Feature;

use App\Http\Helper\NameHelper;
use App\Branch;
use App\Patient;
use App\Role;
use App\Services\PatientService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 首拼检索：打 lwy 就能搜到「刘万友」。
 *
 * 前台接电话时打首拼比打中文快得多，是中文诊所软件的标配检索方式
 * （同类产品的搜索框提示词就是「姓名/首拼/手机号/病历号」）。
 *
 * 这组用例钉四件事：
 *   1. 首拼真的能搜到人，且原有的中文姓名 / 手机号 / 病历号检索没被破坏
 *   2. 首拼是落库字段并跟着姓名走 —— 改名之后旧首拼不该还能搜到
 *   3. 所有写入路径都有首拼（钩子在模型上，不是在某个 Service 里）
 *   4. 首拼走前缀匹配而不是两头模糊 —— 否则两三个字母能扫出一大片无关患者
 */
class PatientPinyinSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->app->setLocale('zh-CN');
    }

    private function makePatient(string $surname, string $othername, string $phone, string $no): Patient
    {
        return Patient::create([
            'patient_no' => $no,
            'surname'    => $surname,
            'othername'  => $othername,
            'gender'     => 'Male',
            'phone_no'   => $phone,
            '_who_added' => $this->staff->id,
        ]);
    }

    private function search(string $keyword): array
    {
        // fullData=true 才返回模型集合；默认返回 [{id,text}] 数组
        return app(PatientService::class)
            ->searchPatients($keyword, true)
            ->pluck('id')
            ->all();
    }

    /** @test */
    public function 首拼能搜到患者(): void
    {
        $liu = $this->makePatient('刘', '万友', '13800130001', 'P001');
        $this->makePatient('张', '伟', '13800130002', 'P002');

        $this->assertSame([$liu->id], $this->search('lwy'));
    }

    /** @test */
    public function 首拼大小写都认(): void
    {
        $liu = $this->makePatient('刘', '万友', '13800130001', 'P001');

        $this->assertSame([$liu->id], $this->search('LWY'));
        $this->assertSame([$liu->id], $this->search('Lwy'));
    }

    /**
     * 首拼是加出来的检索方式，不能把原来的挤掉。
     */
    /** @test */
    public function 中文姓名与手机号病历号检索照常(): void
    {
        $liu = $this->makePatient('刘', '万友', '13800130001', 'P001');

        $this->assertSame([$liu->id], $this->search('刘万友'), '中文全名');
        $this->assertSame([$liu->id], $this->search('万友'), '名');
        $this->assertSame([$liu->id], $this->search('13800130001'), '手机号');
        $this->assertSame([$liu->id], $this->search('P001'), '病历号');
    }

    /**
     * 首拼落库并跟着姓名走：改完名字，旧首拼就不该再命中。
     * 如果首拼是建档时算一次就不管了，这条会挂。
     */
    /** @test */
    public function 改名后首拼跟着变(): void
    {
        $p = $this->makePatient('刘', '万友', '13800130001', 'P001');
        $this->assertSame('lwy', $p->fresh()->name_py);

        $p->update(['surname' => '李', 'othername' => '娜']);

        $this->assertSame('ln', $p->fresh()->name_py);
        $this->assertSame([], $this->search('lwy'), '旧首拼不该还能搜到');
        $this->assertSame([$p->id], $this->search('ln'));
    }

    /**
     * 钩子挂在模型上而不是某个 Service 里，因为患者有五条写入路径
     * （建档、API、在线预约、Excel 导入、OCR）。这里用最底层的
     * Patient::create 验证：只要走模型，首拼就一定有。
     */
    /** @test */
    public function 任何写入路径建的患者都有首拼(): void
    {
        $p = $this->makePatient('欧阳', '娜娜', '13800130003', 'P003');

        $this->assertSame('oynn', $p->fresh()->name_py);
        $this->assertSame([$p->id], $this->search('oynn'));
    }

    /**
     * 前缀匹配而不是两头模糊：lwy 命中「刘万友」，wy 不该命中。
     * 两头模糊的话，前台随便打两个字母就能扫出一大片无关患者。
     */
    /** @test */
    public function 首拼是前缀匹配不是两头模糊(): void
    {
        $this->makePatient('刘', '万友', '13800130001', 'P001');

        $this->assertSame([], $this->search('wy'), 'wy 不该命中 lwy');
    }

    /**
     * 纯数字或含中文的关键词不该去碰首拼列 —— 首拼只有字母。
     */
    /** @test */
    public function 非字母关键词不触发首拼匹配(): void
    {
        $this->makePatient('刘', '万友', '13800130001', 'P001');

        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = $q->sql; });
        $this->search('13800');

        $this->assertNotEmpty($sql);
        $this->assertStringNotContainsString('name_py', implode(' ', $sql));
    }

    /** @test */
    public function 英文姓名取小写字母(): void
    {
        $this->assertSame('lwy', NameHelper::abbr('刘万友'));
        $this->assertSame('adminuser', NameHelper::abbr('Admin User'));
        $this->assertSame('', NameHelper::abbr('   '));
    }

    /**
     * 首拼是姓名的派生值，不该由请求直接写入 —— 否则可以建一个
     * 姓名叫「刘万友」而首拼是「zs」的患者，检索结果就开始骗人。
     */
    /** @test */
    public function 首拼不可由请求直接写入(): void
    {
        $p = Patient::create([
            'patient_no' => 'P009',
            'surname'    => '刘',
            'othername'  => '万友',
            'gender'     => 'Male',
            'phone_no'   => '13800130009',
            'name_py'    => 'zs',          // 试图伪造
            '_who_added' => $this->staff->id,
        ]);

        $this->assertSame('lwy', $p->fresh()->name_py);
    }
}
